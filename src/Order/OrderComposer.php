<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Order\Mapping\QuoteErrorMapping;
use Shopgate\Shopware\Order\Mapping\ShippingMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use ShopgateCartBase;
use ShopgateDeliveryNote;
use ShopgateLibraryException;
use ShopgateMerchantApi;
use ShopgateMerchantApiException;
use ShopgateOrder;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryProcessor;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class OrderComposer
{
    protected const statusesShipped = ['shipped', 'completed'];
    protected const statusesCancelled = ['refunded', 'cancelled'];
    /** @var ContextManager */
    private $contextManager;
    /** @var LineItemComposer */
    private $lineItemComposer;
    /** @var ShippingMapping */
    private $shippingMapping;
    /** @var ShippingMethodBridge */
    private $shippingBridge;
    /** @var CustomerMapping */
    private $customerMapping;
    /** @var ShopgateOrderBridge */
    private $shopgateOrderBridge;
    /** @var QuoteBridge */
    private $quoteBridge;
    /** @var CustomerComposer */
    private $customerComposer;
    /** @var AddressComposer */
    private $addressComposer;
    /** @var QuoteErrorMapping */
    private $errorMapping;

    /**
     * @param ContextManager $contextManager
     * @param LineItemComposer $lineItemComposer
     * @param ShippingMapping $shippingMapping
     * @param ShippingMethodBridge $shippingBridge
     * @param CustomerMapping $customerMapping
     * @param ShopgateOrderBridge $shopgateOrderBridge
     * @param QuoteBridge $quoteBridge
     * @param CustomerComposer $customerComposer
     * @param AddressComposer $addressComposer
     * @param QuoteErrorMapping $errorMapping
     */
    public function __construct(
        ContextManager $contextManager,
        LineItemComposer $lineItemComposer,
        ShippingMapping $shippingMapping,
        ShippingMethodBridge $shippingBridge,
        CustomerMapping $customerMapping,
        ShopgateOrderBridge $shopgateOrderBridge,
        QuoteBridge $quoteBridge,
        CustomerComposer $customerComposer,
        AddressComposer $addressComposer,
        QuoteErrorMapping $errorMapping
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->shippingMapping = $shippingMapping;
        $this->shippingBridge = $shippingBridge;
        $this->customerMapping = $customerMapping;
        $this->shopgateOrderBridge = $shopgateOrderBridge;
        $this->quoteBridge = $quoteBridge;
        $this->customerComposer = $customerComposer;
        $this->addressComposer = $addressComposer;
        $this->errorMapping = $errorMapping;
    }

    /**
     * @param ExtendedCart $sgCart
     * @return array
     * @throws MissingContextException
     */
    public function checkCart(ExtendedCart $sgCart): array
    {
        $context = $this->getContextByCustomer($sgCart->getExternalCustomerId() ?? '');
        $swCart = $this->checkoutBuilder($context, $sgCart);
        $items = $this->lineItemComposer->mapOutgoingLineItems($swCart, $sgCart);
        $deliveries = $this->shippingBridge->getCalculatedDeliveries($context);
        $result = [
                'currency' => $context->getCurrency()->getIsoCode(),
                'shipping_methods' => $this->shippingMapping->mapShippingMethods($deliveries),
                'payment_methods' => [],
                'customer' => $this->customerMapping->mapCartCustomer($context),
            ] + $items;

        $this->quoteBridge->deleteCart($context);

        return $result;
    }

    /**
     * @param string $customerNumber
     * @return SalesChannelContext
     * @throws MissingContextException
     */
    private function getContextByCustomer(string $customerNumber): SalesChannelContext
    {
        try {
            return $this->contextManager->loadByCustomerId($customerNumber);
        } catch (Throwable $e) {
            return $this->contextManager->getSalesContext();
        }
    }

    /**
     * @param SalesChannelContext $context
     * @param ShopgateCartBase $cart
     * @return Cart
     */
    protected function checkoutBuilder(SalesChannelContext $context, ShopgateCartBase $cart): Cart
    {
        $shopwareCart = $this->quoteBridge->loadCartFromContext($context);
        /** @noinspection UnnecessaryCastingInspection */
        $price = new CalculatedPrice(
            (float)($cart->getAmountShipping() ?? $cart->getShippingInfos()->getAmountNet()),
            (float)($cart->getAmountShipping() ?? $cart->getShippingInfos()->getAmountNet()),
            $shopwareCart->getShippingCosts()->getCalculatedTaxes(),
            $shopwareCart->getShippingCosts()->getTaxRules()
        );
        $shopwareCart->addExtension(DeliveryProcessor::MANUAL_SHIPPING_COSTS, $price);
        $lineItems = $this->lineItemComposer->mapIncomingLineItems($cart);
        $request = new Request();
        $request->request->set('items', $lineItems);

        return $this->quoteBridge->addLineItemToQuote($request, $shopwareCart, $context);
    }

    /**
     * @param ShopgateOrder $order
     * @return array
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order): array
    {
        $customerId = $order->getExternalCustomerId();
        // if is guest
        if (empty($customerId)) {
            $detailCustomer = $this->customerMapping->orderToShopgateCustomer($order);
            $customerId = $this->customerComposer->registerCustomer(null, $detailCustomer)->getId();
        }
        $channel = $this->getContextByCustomer($customerId ?? '');
        if ($this->shopgateOrderBridge->orderExists((string)$order->getOrderNumber(), $channel)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                $order->getOrderNumber(),
                true
            );
        }

        $addressBag = $this->addressComposer->createAddressSwitchData($order, $channel);
        $dataBag = [
            SalesChannelContextService::PAYMENT_METHOD_ID => GenericPayment::UUID,
            SalesChannelContextService::SHIPPING_METHOD_ID => GenericShippingMethod::UUID
        ];

        try {
            // making sure that 2 address ID's are different from each other
            if (count(array_unique($addressBag)) === 2) {
                // dirty hack because of some validation bug that causes to keep billing address ID in search criteria
                $dataBag[SalesChannelContextService::BILLING_ADDRESS_ID] = array_pop($addressBag);
                $this->contextManager->switchContext(new RequestDataBag($dataBag));
                $dataBag[SalesChannelContextService::SHIPPING_ADDRESS_ID] = array_pop($addressBag);
                unset($dataBag[SalesChannelContextService::BILLING_ADDRESS_ID]);
                $newContext = $this->contextManager->switchContext(new RequestDataBag($dataBag));
            } else {
                $newContext = $this->contextManager->switchContext(
                    new RequestDataBag(array_merge($dataBag, $addressBag))
                );
            }
        } catch (ConstraintViolationException $exception) {
            throw $this->errorMapping->mapConstraintError($exception);
        }

        $swCart = $this->checkoutBuilder($newContext, $order);
        $swCart->setErrors($swCart->getErrors()->filter(function (Error $error) {
            return $error->isPersistent() === false;
        }));
        try {
            $swOrder = $this->quoteBridge->createOrder($swCart, $newContext);
        } catch (InvalidCartException $error) {
            throw $this->errorMapping->mapInvalidCartError($error);
        } catch (ConstraintViolationException $exception) {
            throw $this->errorMapping->mapConstraintError($exception);
        } catch (Throwable $error) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                $error->getMessage(),
                true
            );
        }
        $this->shopgateOrderBridge->saveEntity(
            (new ShopgateOrderEntity())->mapQuote($swOrder->getId(), $newContext->getSalesChannel()->getId(), $order),
            $newContext
        );

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
