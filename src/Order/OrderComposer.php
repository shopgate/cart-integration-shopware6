<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Customer\OrderCustomerComposer;
use Shopgate\Shopware\Order\LineItem\LineItemComposer;
use Shopgate\Shopware\Order\Payment\PaymentComposer;
use Shopgate\Shopware\Order\Quote\GetOrdersCriteria;
use Shopgate\Shopware\Order\Quote\OrderMapping;
use Shopgate\Shopware\Order\Quote\QuoteBridge;
use Shopgate\Shopware\Order\Quote\QuoteErrorMapping;
use Shopgate\Shopware\Order\Shipping\ShippingComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Shopgate\NativeOrderExtension;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethod;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateDeliveryNote;
use ShopgateExternalOrder;
use ShopgateLibraryException;
use ShopgateMerchantApi;
use ShopgateMerchantApiException;
use ShopgateOrder;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class OrderComposer
{
    protected const statusesShipped = [OrderDeliveryStates::STATE_SHIPPED, OrderStates::STATE_COMPLETED];
    protected const statusesCancelled = [OrderTransactionStates::STATE_REFUNDED, OrderStates::STATE_CANCELLED];

    private ContextComposer $contextComposer;
    private ContextManager $contextManager;
    private LineItemComposer $lineItemComposer;
    private QuoteBridge $quoteBridge;
    private QuoteErrorMapping $errorMapping;
    private ShippingComposer $shippingComposer;
    private ShopgateOrderBridge $shopgateOrderBridge;
    private PaymentComposer $paymentComposer;
    private OrderCustomerComposer $orderCustomerComposer;
    private OrderMapping $orderMapping;
    private LoggerInterface $logger;

    public function __construct(
        ContextManager $contextManager,
        ContextComposer $contextComposer,
        LineItemComposer $lineItemComposer,
        QuoteBridge $quoteBridge,
        QuoteErrorMapping $errorMapping,
        ShopgateOrderBridge $shopgateOrderBridge,
        ShippingComposer $shippingComposer,
        PaymentComposer $paymentComposer,
        OrderCustomerComposer $orderCustomerComposer,
        OrderMapping $orderMapping,
        LoggerInterface $logger
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->shopgateOrderBridge = $shopgateOrderBridge;
        $this->quoteBridge = $quoteBridge;
        $this->errorMapping = $errorMapping;
        $this->shippingComposer = $shippingComposer;
        $this->contextComposer = $contextComposer;
        $this->paymentComposer = $paymentComposer;
        $this->orderCustomerComposer = $orderCustomerComposer;
        $this->orderMapping = $orderMapping;
        $this->logger = $logger;
    }

    /**
     * @param ExtendedOrder|ShopgateOrder $order
     * @return array
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order): array
    {
        $customerId = $order->getExternalCustomerId();
        if ($order->isGuest() && $order->getMail()) {
            $customerId = $this->orderCustomerComposer->getOrCreateGuestCustomerByEmail(
                $order->getMail(),
                $order,
                $this->contextManager->getSalesContext()
            )->getId();
        }
        // load desktop cart, duplicate its context, add info to context & create new cart based on it
        $initContext = $this->contextComposer->getContextByCustomerId($customerId ?? '');
        $cleanCartContext = $this->contextManager->duplicateContextWithNewToken($initContext, $customerId ?? null);
        if ($this->shopgateOrderBridge->orderExists((string)$order->getOrderNumber(),
            $cleanCartContext->getContext())) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                $order->getOrderNumber(),
                true
            );
        }

        $this->contextComposer->addCustomerAddress($order, $cleanCartContext);
        $paymentId = $this->paymentComposer->mapIncomingPayment($order, $cleanCartContext);
        $dataBag = [
            SalesChannelContextService::SHIPPING_METHOD_ID =>
                $order->isShippingFree() ? FreeShippingMethod::UUID : GenericShippingMethod::UUID,
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentId
        ];
        try {
            $newContext = $this->contextManager->switchContext(new RequestDataBag($dataBag), $cleanCartContext);
            $shopwareCart = $this->quoteBridge->loadCartFromContext($newContext);
            if (!$order->isShippingFree()) {
                $this->shippingComposer->addShippingFeeToCart($order, $shopwareCart);
            }
            $lineItems = $this->lineItemComposer->mapIncomingLineItems($order);
            $swCart = $this->lineItemComposer->addLineItemsToCart($shopwareCart, $newContext, $lineItems);
            // some errors are just success notifications, so we have to remove them
            $swCart->setErrors($swCart->getErrors()->filter(
                fn(Error $error) => $error->isPersistent() === false)
            );
            // creates order & sends email
            $swOrder = $this->quoteBridge->createOrder($swCart, $newContext);
            $this->quoteBridge->updateOrder(
                $swOrder->getId(),
                $this->orderMapping->mapIncomingOrder($order),
                $newContext
            );
            // order status update
            if ($order->getIsPaid()) {
                $this->setOrderPaid($swOrder, $newContext);
            }
            if (!$order->getIsShippingBlocked() && $order->getIsShippingCompleted()) {
                $this->setOrderShipped($swOrder, $newContext);
            }
        } catch (InvalidCartException $error) {
            throw $this->errorMapping->mapInvalidCartError($error);
        } catch (ConstraintViolationException $exception) {
            throw $this->errorMapping->mapConstraintError($exception);
        } catch (ShopwareHttpException $error) {
            throw $this->errorMapping->mapGenericHttpException($error);
        } catch (Throwable $error) {
            throw $this->errorMapping->mapThrowable($error);
        } finally {
            $this->contextManager->resetContext($initContext); // load original desktop cart
        }

        return [
            'external_order_id' => $swOrder->getId(),
            'external_order_number' => $swOrder->getOrderNumber()
        ];
    }

    /**
     * @return ShopgateExternalOrder[]
     * @throws MissingContextException
     */
    public function getOrders(
        string $id,
        int $limit,
        int $offset,
        string $sortOrder,
        ?string $orderDateFrom = null
    ): array {
        $criteria = (new GetOrdersCriteria())
            ->setLimit($limit)
            ->setOffset($offset)
            ->setShopgateSort($sortOrder)
            ->setShopgateFromDate($orderDateFrom)
            ->addShopgateAssociations();
        $criteria->getAssociation('lineItems')->addFilter(new EqualsFilter('parentId', null));

        $initContext = $this->contextComposer->getContextByCustomerId($id);
        $orderResponse = $this->quoteBridge->getOrdersAsCustomer(new Request(), $criteria, $initContext);

        return $orderResponse->getOrders()->map(
            fn(OrderEntity $entity) => $this->orderMapping->mapOutgoingOrder($entity)
        );
    }

    /**
     * @throws ShopgateLibraryException
     * @throws ShopgateMerchantApiException
     */
    public function setShippingCompleted(ShopgateMerchantApi $merchantApi): void
    {
        $context = $this->contextManager->getSalesContext()->getContext();
        $shopgateOrders = $this->shopgateOrderBridge->getOrdersNotSynced($context);
        foreach ($shopgateOrders as $shopgateOrder) {
            $swOrder = $shopgateOrder->getOrder();
            if ($swOrder === null) {
                // should not happen, but in this case the order shouldn't be handled again
                $shopgateOrder->setIsSent(true);
                $this->shopgateOrderBridge->saveEntity($shopgateOrder, $context);
                continue;
            }
            $stateName = $swOrder->getStateMachineState() ? $swOrder->getStateMachineState()->getTechnicalName() : '';
            if (in_array($stateName, self::statusesShipped, true)) {
                $merchantApi->addOrderDeliveryNote(
                    $shopgateOrder->getShopgateOrderNumber(),
                    ShopgateDeliveryNote::OTHER,
                    '',
                    true
                );
                $shopgateOrder->setIsSent(true);
                $this->shopgateOrderBridge->saveEntity($shopgateOrder, $context);
            }
        }
    }

    /**
     * @throws ShopgateLibraryException
     * @throws ShopgateMerchantApiException
     */
    public function cancelOrders(ShopgateMerchantApi $merchantApi): void
    {
        $context = $this->contextManager->getSalesContext()->getContext();
        $shopgateOrders = $this->shopgateOrderBridge->getOrdersNotSynced($context);
        foreach ($shopgateOrders as $shopgateOrder) {
            $swOrder = $shopgateOrder->getOrder();
            if ($swOrder === null) {
                // should not happen, but in this case the order shouldn't be handled again
                $shopgateOrder->setIsCancelled(true);
                $this->shopgateOrderBridge->saveEntity($shopgateOrder, $context);
                continue;
            }
            $stateName = $swOrder->getStateMachineState() ? $swOrder->getStateMachineState()->getTechnicalName() : '';
            if (in_array($stateName, self::statusesCancelled, true)) {
                $merchantApi->cancelOrder($shopgateOrder->getShopgateOrderNumber());
                $shopgateOrder->setIsCancelled(true);
                $this->shopgateOrderBridge->saveEntity($shopgateOrder, $context);
            }
        }
    }

    /**
     * We may need to rethink shipping blocking here
     *
     * @throws ShopgateLibraryException
     * @throws MissingContextException
     */
    public function updateOrder(ShopgateOrder $incSgOrder): array
    {
        $channel = $this->contextManager->getSalesContext();
        $criteria = (new GetOrdersCriteria())
            ->addStateAssociations()
            ->addAssociations([NativeOrderExtension::PROPERTY])
            ->addFilter(new EqualsFilter(NativeOrderExtension::PROPERTY . '.shopgateOrderNumber',
                $incSgOrder->getOrderNumber()));
        $swOrder = $this->quoteBridge->getOrders($criteria, $channel)->first();
        if (null === $swOrder) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_ORDER_NOT_FOUND,
                $incSgOrder->getOrderNumber(),
                true
            );
        }
        /** @var ShopgateOrderEntity $extension */
        $extension = $swOrder->getExtension(NativeOrderExtension::PROPERTY);

        if ($incSgOrder->getUpdatePayment() && $incSgOrder->getIsPaid()) {
            $extension->setIsPaid((bool)$incSgOrder->getIsPaid());
            if (false === $this->paymentComposer->isPaid($swOrder->getTransactions())) {
                $this->setOrderPaid($swOrder, $channel);
            }
        }

        // easier to manage deliveries if they are sorted by price
        $this->shippingComposer->sortDeliveries($swOrder->getDeliveries());
        if ($incSgOrder->getUpdateShipping()
            && !$incSgOrder->getIsShippingBlocked()
            && $incSgOrder->getIsShippingCompleted()
            && false === $this->shippingComposer->isFullyShipped($swOrder->getDeliveries())
        ) {
            $this->setOrderShipped($swOrder, $channel);
        }
        $this->shopgateOrderBridge->saveEntity($extension, $channel->getContext());

        return [
            'external_order_id' => $swOrder->getId(),
            'external_order_number' => $swOrder->getOrderNumber()
        ];
    }

    public function setOrderPaid(OrderEntity $swOrder, SalesChannelContext $context): void
    {
        $result = $this->paymentComposer->setToPaid($swOrder->getTransactions(), $context);
        if (!$result) {
            $this->logger->debug('Could not transition order to "Paid" status');
            $this->logger->debug($swOrder->getTransactions());
        }
    }

    public function setOrderShipped(OrderEntity $swOrder, SalesChannelContext $context): void
    {
        $result = $this->shippingComposer->setToShipped($swOrder->getDeliveries(), $context);
        if (!$result) {
            $this->logger->debug('Could not transition order to "Shipped" status');
            $this->logger->debug($swOrder->getDeliveries());
        }
    }
}
