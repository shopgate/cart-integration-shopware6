<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Order\Mapping\ShippingMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use ShopgateCartBase;
use ShopgateLibraryException;
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

    /**
     * @param ContextManager $contextManager
     * @param LineItemComposer $lineItemComposer
     * @param ShippingMapping $shippingMapping
     * @param ShippingMethodBridge $shippingBridge
     * @param CustomerMapping $customerMapping
     * @param ShopgateOrderBridge $shopgateOrderBridge
     * @param QuoteBridge $quoteBridge
     * @param CustomerComposer $customerComposer
     */
    public function __construct(
        ContextManager $contextManager,
        LineItemComposer $lineItemComposer,
        ShippingMapping $shippingMapping,
        ShippingMethodBridge $shippingBridge,
        CustomerMapping $customerMapping,
        ShopgateOrderBridge $shopgateOrderBridge,
        QuoteBridge $quoteBridge,
        CustomerComposer $customerComposer
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->shippingMapping = $shippingMapping;
        $this->shippingBridge = $shippingBridge;
        $this->customerMapping = $customerMapping;
        $this->shopgateOrderBridge = $shopgateOrderBridge;
        $this->quoteBridge = $quoteBridge;
        $this->customerComposer = $customerComposer;
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

        return [
                'currency' => $context->getCurrency()->getIsoCode(),
                'shipping_methods' => $this->shippingMapping->mapShippingMethods($deliveries),
                'payment_methods' => [],
                'customer' => $this->customerMapping->mapCartCustomer($context),
            ] + $items;
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
            $customer = $this->customerMapping->orderToShopgateCustomer($order);
            $this->customerComposer->registerCustomer(null, $customer);
        }
        $channel = $this->getContextByCustomer($customerId??'');
        if ($this->shopgateOrderBridge->orderExists($order->getOrderNumber(), $channel)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                $order->getOrderNumber(),
                true
            );
        }
        $dataBag = new RequestDataBag(
            array_merge(
                [
                    SalesChannelContextService::PAYMENT_METHOD_ID => GenericPayment::UUID,
                    SalesChannelContextService::SHIPPING_METHOD_ID => GenericShippingMethod::UUID
                ],
                $order->getDeliveryAddress()->getId() && $channel->getCustomer()
                    ? [SalesChannelContextService::SHIPPING_ADDRESS_ID => $order->getDeliveryAddress()->getId()]
                    : [],
                $order->getInvoiceAddress()->getId() && $channel->getCustomer()
                    ? [SalesChannelContextService::BILLING_ADDRESS_ID => $order->getInvoiceAddress()->getId()]
                    : []
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
     * @throws MissingContextException
     */
    public function setShippingCompleted(): void
    {
        $shopgateOrders = $this->shopgateOrderBridge->getOrdersNotSynced($this->contextManager->getSalesContext());
        foreach ($shopgateOrders as $shopgateOrder) {
            $a = $shopgateOrder->getReceivedData();
        }
    }

    /**
     * @throws MissingContextException
     */
    public function cancelOrders(): void
    {
        $shopgateOrders = $this->shopgateOrderBridge->getOrdersNotSynced($this->contextManager->getSalesContext());
    }
}
