<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Order\Mapping\QuoteErrorMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
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
    private CustomerComposer $customerComposer;
    private CustomerMapping $customerMapping;
    private LineItemComposer $lineItemComposer;
    private QuoteBridge $quoteBridge;
    private QuoteErrorMapping $errorMapping;
    private ShippingComposer $shippingComposer;
    private ShopgateOrderBridge $shopgateOrderBridge;

    /**
     * @param ContextManager $contextManager
     * @param ContextComposer $contextComposer
     * @param LineItemComposer $lineItemComposer
     * @param CustomerMapping $customerMapping
     * @param QuoteBridge $quoteBridge
     * @param QuoteErrorMapping $errorMapping
     * @param ShopgateOrderBridge $shopgateOrderBridge
     * @param CustomerComposer $customerComposer
     * @param ShippingComposer $shippingComposer
     */
    public function __construct(
        ContextManager $contextManager,
        ContextComposer $contextComposer,
        LineItemComposer $lineItemComposer,
        CustomerMapping $customerMapping,
        QuoteBridge $quoteBridge,
        QuoteErrorMapping $errorMapping,
        ShopgateOrderBridge $shopgateOrderBridge,
        CustomerComposer $customerComposer,
        ShippingComposer $shippingComposer
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->customerMapping = $customerMapping;
        $this->shopgateOrderBridge = $shopgateOrderBridge;
        $this->quoteBridge = $quoteBridge;
        $this->customerComposer = $customerComposer;
        $this->errorMapping = $errorMapping;
        $this->shippingComposer = $shippingComposer;
        $this->contextComposer = $contextComposer;
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
        // if is guest
        if (empty($customerId)) {
            $detailCustomer = $this->customerMapping->orderToShopgateCustomer($order);
            $customerId = $this->customerComposer->registerCustomer(null, $detailCustomer)->getId();
        }
        $channel = $this->contextComposer->getContextByCustomerId($customerId ?? '');
        if ($this->shopgateOrderBridge->orderExists((string)$order->getOrderNumber(), $channel)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                $order->getOrderNumber(),
                true
            );
        }

        $this->contextComposer->addCustomerAddress($order, $channel);
        $dataBag = [
            SalesChannelContextService::PAYMENT_METHOD_ID => GenericPayment::UUID,
            SalesChannelContextService::SHIPPING_METHOD_ID =>
                $order->isShippingFree() ? FreeShippingMethod::UUID : GenericShippingMethod::UUID
        ];
        try {
            $newContext = $this->contextManager->switchContext(new RequestDataBag($dataBag));
            // build cart & order
            $shopwareCart = $this->quoteBridge->loadCartFromContext($newContext);
            if (!$order->isShippingFree()) {
                $this->shippingComposer->addShippingFee($order, $shopwareCart);
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
