<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Order\Mapping\ShippingMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use ShopgateCartBase;
use ShopgateLibraryException;
use ShopgateOrder;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
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

    /**
     * @param ContextManager $contextManager
     * @param LineItemComposer $lineItemComposer
     * @param ShippingMapping $shippingMapping
     * @param ShippingMethodBridge $shippingBridge
     * @param CustomerMapping $customerMapping
     * @param ShopgateOrderBridge $shopgateOrderBridge
     * @param QuoteBridge $quoteBridge
     */
    public function __construct(
        ContextManager $contextManager,
        LineItemComposer $lineItemComposer,
        ShippingMapping $shippingMapping,
        ShippingMethodBridge $shippingBridge,
        CustomerMapping $customerMapping,
        ShopgateOrderBridge $shopgateOrderBridge,
        QuoteBridge $quoteBridge
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->shippingMapping = $shippingMapping;
        $this->shippingBridge = $shippingBridge;
        $this->customerMapping = $customerMapping;
        $this->shopgateOrderBridge = $shopgateOrderBridge;
        $this->quoteBridge = $quoteBridge;
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
                'payment_methods' => [], // out of scope
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
            //todo: log, issue with customer therefore load guest cart?
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
        $channel = $this->getContextByCustomer($order->getExternalCustomerId() ?? '');
        if ($this->shopgateOrderBridge->orderExists($order->getOrderNumber(), $channel)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                $order->getOrderNumber(),
                true
            );
        }
        //todo: needed implementation if sg_address->id is not provided
//        $deliveryId = $this->customerMapping->getSelectedAddressId($order->getDeliveryAddress(), $channel);
//        if (!$deliveryId) {
//            //todo: create delivery address for this customer and get ID
//            $deliveryId = '';
//        }
        $dataBag = new RequestDataBag(
            array_merge(
                [
                    SalesChannelContextService::PAYMENT_METHOD_ID => GenericPayment::UUID,
                    SalesChannelContextService::SHIPPING_METHOD_ID => '40ba83a5e6aa448caeb2387ba85f733d'
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
        // todo: fix issue with payment appearing on frontend (deactivation doesn't work)
        $swCart = $this->checkoutBuilder($newContext, $order);
        $swCart->setErrors($swCart->getErrors()->filter(function (Error $error) {
            return $error->isPersistent() === false; //todo: test all errors
        }));
        $swOrder = $this->quoteBridge->createOrder($swCart, $channel);
        $this->shopgateOrderBridge->saveEntity(
            (new ShopgateOrderEntity())->mapQuote($swOrder->getId(), $channel->getSalesChannelId(), $order),
            $channel
        );

        return [
            'external_order_id' => $swOrder->getId(),
            'external_order_number' => $swOrder->getOrderNumber()
        ];
    }
}
