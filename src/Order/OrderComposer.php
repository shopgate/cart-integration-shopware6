<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Customer\AddressBridge;
use Shopgate\Shopware\Customer\CustomerBridge;
use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Customer\Mapping\AddressMapping;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Order\Mapping\ShippingMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use ShopgateAddress;
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
    /** @var AddressMapping */
    private $addressMapping;
    /** @var AddressBridge */
    private $addressBridge;
    /** @var CustomerBridge */
    private $customerBridge;

    /**
     * @param ContextManager $contextManager
     * @param LineItemComposer $lineItemComposer
     * @param ShippingMapping $shippingMapping
     * @param ShippingMethodBridge $shippingBridge
     * @param CustomerMapping $customerMapping
     * @param ShopgateOrderBridge $shopgateOrderBridge
     * @param QuoteBridge $quoteBridge
     * @param CustomerComposer $customerComposer
     * @param CustomerBridge $customerBridge
     * @param AddressMapping $addressMapping
     * @param AddressBridge $addressBridge
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
        CustomerBridge $customerBridge,
        AddressMapping $addressMapping,
        AddressBridge $addressBridge
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->shippingMapping = $shippingMapping;
        $this->shippingBridge = $shippingBridge;
        $this->customerMapping = $customerMapping;
        $this->shopgateOrderBridge = $shopgateOrderBridge;
        $this->quoteBridge = $quoteBridge;
        $this->customerComposer = $customerComposer;
        $this->addressMapping = $addressMapping;
        $this->addressBridge = $addressBridge;
        $this->customerBridge = $customerBridge;
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
        $price = new CalculatedPrice(
            $cart->getAmountShipping(),
            $cart->getAmountShipping(),
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
        if ($this->shopgateOrderBridge->orderExists($order->getOrderNumber(), $channel)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                $order->getOrderNumber(),
                true
            );
        }

        /**
         * Logged in customer, map incoming data to existing addresses or create new ones.
         * In case of failure, shopware order creation will use default shopware customer addresses.
         */
        $addressBag = [];
        if ($order->getExternalCustomerId() && $channel->getCustomer()) {
            $deliveryId = $this->getOrCreateAddress($order->getDeliveryAddress(), $channel);
            $invoiceId = $this->getOrCreateAddress($order->getInvoiceAddress(), $channel);
            $addressBag = [
                SalesChannelContextService::SHIPPING_ADDRESS_ID => $deliveryId,
                SalesChannelContextService::BILLING_ADDRESS_ID => $invoiceId
            ];
        }

        $dataBag = new RequestDataBag(
            array_merge(
                [
                    SalesChannelContextService::PAYMENT_METHOD_ID => GenericPayment::UUID,
                    SalesChannelContextService::SHIPPING_METHOD_ID => GenericShippingMethod::UUID
                ],
                $addressBag
            )
        );

        $newContext = $this->contextManager->switchContext($dataBag);
        $swCart = $this->checkoutBuilder($newContext, $order);
        $swCart->setErrors($swCart->getErrors()->filter(function (Error $error) {
            return $error->isPersistent() === false;
        }));
        try {
            $swOrder = $this->quoteBridge->createOrder($swCart, $newContext);
        } catch (InvalidCartException $error) {
            $elements = $error->getCartErrors()->getElements();
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                array_pop($elements)->getMessage(),
                true //todo-prod: change to false
            );
        } catch (Throwable $error) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                $error->getMessage()
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
     * Checks existing customer addresses & creates one if necessary
     *
     * @param ShopgateAddress $address
     * @param SalesChannelContext $context
     * @return string|null
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    private function getOrCreateAddress(ShopgateAddress $address, SalesChannelContext $context): ?string
    {
        $customer = $this->customerBridge->getDetailedContextCustomer($context);
        $addressId = $this->addressMapping->getSelectedAddressId($address, $customer);
        if (!$addressId) {
            $shopwareAddress = $this->addressMapping->mapToShopwareAddress($address);
            $addressId = $this->addressBridge->addAddress($shopwareAddress, $context, $customer)->getId();
        }
        if (!$addressId) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_NO_ADDRESSES_FOUND,
                var_export($address, true),
                true
            );
        }
        return $addressId;
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
            $swOrder = $this->quoteBridge->loadOrderById($shopgateOrder->getShopwareOrderId(),
                $this->contextManager->getSalesContext());
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
            $swOrder = $this->quoteBridge->loadOrderById($shopgateOrder->getShopwareOrderId(),
                $this->contextManager->getSalesContext());
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
