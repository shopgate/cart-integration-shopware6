<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Customer\OrderCustomerComposer;
use Shopgate\Shopware\Order\LineItem\LineItemComposer;
use Shopgate\Shopware\Order\Payment\PaymentComposer;
use Shopgate\Shopware\Order\Quote\QuoteBridge;
use Shopgate\Shopware\Order\Quote\QuoteErrorMapping;
use Shopgate\Shopware\Order\Shipping\ShippingComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethod;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use ShopgateDeliveryNote;
use ShopgateLibraryException;
use ShopgateMerchantApi;
use ShopgateMerchantApiException;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Throwable;

class OrderComposer
{
    protected const statusesShipped = ['shipped', 'completed'];
    protected const statusesCancelled = ['refunded', 'cancelled'];

    private ContextComposer $contextComposer;
    private ContextManager $contextManager;
    private LineItemComposer $lineItemComposer;
    private QuoteBridge $quoteBridge;
    private QuoteErrorMapping $errorMapping;
    private ShippingComposer $shippingComposer;
    private ShopgateOrderBridge $shopgateOrderBridge;
    private PaymentComposer $paymentComposer;
    private OrderCustomerComposer $orderCustomerComposer;

    public function __construct(
        ContextManager $contextManager,
        ContextComposer $contextComposer,
        LineItemComposer $lineItemComposer,
        QuoteBridge $quoteBridge,
        QuoteErrorMapping $errorMapping,
        ShopgateOrderBridge $shopgateOrderBridge,
        ShippingComposer $shippingComposer,
        PaymentComposer $paymentComposer,
        OrderCustomerComposer $orderCustomerComposer
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
    }

    /**
     * @param ExtendedOrder $order
     * @return array
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function addOrder(ExtendedOrder $order): array
    {
        $customerId = $order->getExternalCustomerId();
        if ($order->isGuest()) {
            $customerId = $this->orderCustomerComposer->getOrCreateGuestCustomer(
                $order,
                $this->contextManager->getSalesContext()
            )->getId();
        }
        $initContext = $this->contextComposer->getContextByCustomerId($customerId ?? '');
        if ($this->shopgateOrderBridge->orderExists((string)$order->getOrderNumber(), $initContext)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                $order->getOrderNumber(),
                true
            );
        }

        $this->contextComposer->addCustomerAddress($order, $initContext);
        $paymentId = $this->paymentComposer->mapIncomingPayment($order, $initContext);
        $dataBag = [
            SalesChannelContextService::SHIPPING_METHOD_ID =>
                $order->isShippingFree() ? FreeShippingMethod::UUID : GenericShippingMethod::UUID,
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentId
        ];
        try {
            $newContext = $this->contextManager->switchContext(new RequestDataBag($dataBag), $initContext);
            // build cart & order
            $shopwareCart = $this->quoteBridge->loadCartFromContext($newContext);
            if (!$order->isShippingFree()) {
                $this->shippingComposer->addShippingFeeToCart($order, $shopwareCart);
            }
            $lineItems = $this->lineItemComposer->mapIncomingLineItems($order);
            $swCart = $this->lineItemComposer->addLineItemsToCart($shopwareCart, $newContext, $lineItems);
            // some errors are just success notifications, remove them
            $swCart->setErrors($swCart->getErrors()->filter(function (Error $error) {
                return $error->isPersistent() === false;
            }));
            $swOrder = $this->quoteBridge->createOrder($swCart, $newContext);
        } catch (InvalidCartException $error) {
            throw $this->errorMapping->mapInvalidCartError($error);
        } catch (ConstraintViolationException $exception) {
            throw $this->errorMapping->mapConstraintError($exception);
        } catch (ShopwareHttpException $error) {
            throw $this->errorMapping->mapGenericHttpException($error);
        } catch (Throwable $error) {
            throw $this->errorMapping->mapThrowable($error);
        }
        $this->shopgateOrderBridge->saveEntity(
            (new ShopgateOrderEntity())->mapQuote($swOrder->getId(), $newContext->getSalesChannel()->getId(), $order),
            $newContext
        );
        $this->contextManager->resetContext();

        return [
            'external_order_id' => $swOrder->getId(),
            'external_order_number' => $swOrder->getOrderNumber()
        ];
    }

    /**
     * @param ShopgateMerchantApi $merchantApi
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     * @throws ShopgateMerchantApiException
     */
    public function setShippingCompleted(ShopgateMerchantApi $merchantApi): void
    {
        $shopgateOrders = $this->shopgateOrderBridge->getOrdersNotSynced($this->contextManager->getSalesContext());
        foreach ($shopgateOrders as $shopgateOrder) {
            $swOrder = $shopgateOrder->getOrder();
            if ($swOrder === null) {
                // should not happen, but in this case the order shouldn't be handled again
                $shopgateOrder->setIsSent(true);
                $this->shopgateOrderBridge->saveEntity($shopgateOrder, $this->contextManager->getSalesContext());
                continue;
            }
            $stateName = $swOrder->getStateMachineState() ? $swOrder->getStateMachineState()->getTechnicalName() : '';
            if (in_array($stateName, self::statusesShipped)) {
                $merchantApi->addOrderDeliveryNote(
                    $shopgateOrder->getShopgateOrderNumber(),
                    ShopgateDeliveryNote::OTHER,
                    '',
                    true
                );
                $shopgateOrder->setIsSent(true);
                $this->shopgateOrderBridge->saveEntity($shopgateOrder, $this->contextManager->getSalesContext());
            }
        }
    }

    /**
     * @param ShopgateMerchantApi $merchantApi
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     * @throws ShopgateMerchantApiException
     */
    public function cancelOrders(ShopgateMerchantApi $merchantApi): void
    {
        $shopgateOrders = $this->shopgateOrderBridge->getOrdersNotSynced($this->contextManager->getSalesContext());
        foreach ($shopgateOrders as $shopgateOrder) {
            $swOrder = $shopgateOrder->getOrder();
            if ($swOrder === null) {
                // should not happen, but in this case the order shouldn't be handled again
                $shopgateOrder->setIsCancelled(true);
                $this->shopgateOrderBridge->saveEntity($shopgateOrder, $this->contextManager->getSalesContext());
                continue;
            }
            $stateName = $swOrder->getStateMachineState() ? $swOrder->getStateMachineState()->getTechnicalName() : '';
            if (in_array($stateName, self::statusesCancelled)) {
                $merchantApi->cancelOrder($shopgateOrder->getShopgateOrderNumber());
                $shopgateOrder->setIsCancelled(true);
                $this->shopgateOrderBridge->saveEntity($shopgateOrder, $this->contextManager->getSalesContext());
            }
        }
    }
}
