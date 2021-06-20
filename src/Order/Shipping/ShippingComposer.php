<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping;

use Shopgate\Shopware\Order\Events\Shipping\AfterShippingMethodMappingEvent;
use Shopgate\Shopware\Order\Events\Shipping\BeforeDeliveryContextSwitchEvent;
use Shopgate\Shopware\Order\Events\Shipping\BeforeShippingMethodMappingEvent;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateShippingMethod;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryProcessor;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ShippingComposer
{
    private ShippingMethodBridge $shippingBridge;
    private CheckoutCartPageLoader $cartPageLoader;
    private ContextManager $contextManager;
    private ShippingMapping $shippingMapping;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @param ShippingMethodBridge $shippingBridge
     * @param ShippingMapping $shippingMapping
     * @param CheckoutCartPageLoader $cartPageLoader
     * @param ContextManager $contextManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ShippingMethodBridge $shippingBridge,
        ShippingMapping $shippingMapping,
        CheckoutCartPageLoader $cartPageLoader,
        ContextManager $contextManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->shippingBridge = $shippingBridge;
        $this->cartPageLoader = $cartPageLoader;
        $this->contextManager = $contextManager;
        $this->shippingMapping = $shippingMapping;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Adds manual shipping fee.
     * Make sure it's not 0.0 value. There is an issue with setting the
     * manual shipping cost to 0. Hence why we need to use our custom
     * Free Shipping method in this case.
     *
     * @param ExtendedOrder $sgOrder
     * @param Cart $swCart
     */
    public function addShippingFeeToCart(ExtendedOrder $sgOrder, Cart $swCart): void
    {
        // overwrite shipping cost when creating an order
        $shippingCost = $sgOrder->getShippingCost();
        $price = new CalculatedPrice(
            $shippingCost,
            $shippingCost,
            $swCart->getShippingCosts()->getCalculatedTaxes(),
            $swCart->getShippingCosts()->getTaxRules()
        );
        $swCart->addExtension(DeliveryProcessor::MANUAL_SHIPPING_COSTS, $price);
    }

    /**
     * @param SalesChannelContext $context
     * @return ShopgateShippingMethod[]
     */
    public function mapShippingMethods(SalesChannelContext $context): array
    {
        $deliveries = $this->getCalculatedDeliveries($context);
        $this->eventDispatcher->dispatch(new BeforeShippingMethodMappingEvent($deliveries));
        $result = new DataBag($this->shippingMapping->mapShippingMethods($deliveries));
        $this->eventDispatcher->dispatch(new AfterShippingMethodMappingEvent($result));
        return $result->all();
    }

    /**
     * @param SalesChannelContext $context
     * @return DeliveryCollection
     */
    public function getCalculatedDeliveries(SalesChannelContext $context): DeliveryCollection
    {
        $shippingMethods = $this->shippingBridge->getDeliveries($context);
        $list = [];
        $request = new Request();
        $request->setSession(new Session()); // support for 3rd party plugins that do not check session existence
        foreach ($shippingMethods->getElements() as $shipMethod) {
            $dataBag = new RequestDataBag([SalesChannelContextService::SHIPPING_METHOD_ID => $shipMethod->getId()]);
            $this->eventDispatcher->dispatch(new BeforeDeliveryContextSwitchEvent($dataBag));
            $context = $this->contextManager->switchContext($dataBag);
            $cart = $this->cartPageLoader->load($request, $context)->getCart();
            foreach ($cart->getDeliveries()->getElements() as $delivery) {
                $list[$delivery->getShippingMethod()->getId()] = $delivery;
            }
        }

        return new DeliveryCollection($list);
    }
}
