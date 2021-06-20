<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Order\Mapping\ShippingMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateShippingMethod;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryProcessor;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class ShippingComposer
{
    private ShippingMethodBridge $shippingBridge;
    private CheckoutCartPageLoader $cartPageLoader;
    private ContextManager $contextManager;
    private ShippingMapping $shippingMapping;

    /**
     * @param ShippingMethodBridge $shippingBridge
     * @param ShippingMapping $shippingMapping
     * @param CheckoutCartPageLoader $cartPageLoader
     * @param ContextManager $contextManager
     */
    public function __construct(
        ShippingMethodBridge $shippingBridge,
        ShippingMapping $shippingMapping,
        CheckoutCartPageLoader $cartPageLoader,
        ContextManager $contextManager
    ) {
        $this->shippingBridge = $shippingBridge;
        $this->cartPageLoader = $cartPageLoader;
        $this->contextManager = $contextManager;
        $this->shippingMapping = $shippingMapping;
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
            $context = $this->contextManager->switchContext($dataBag);
            $cart = $this->cartPageLoader->load($request, $context)->getCart();
            foreach ($cart->getDeliveries()->getElements() as $delivery) {
                $list[$delivery->getShippingMethod()->getId()] = $delivery;
            }
        }

        return new DeliveryCollection($list);
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
    public function addShippingFee(ExtendedOrder $sgOrder, Cart $swCart): void
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
     * @param DeliveryCollection $deliveries
     * @return ShopgateShippingMethod[]
     */
    public function outgoingShippingMethods(DeliveryCollection $deliveries): array
    {
        $result = $this->shippingMapping->mapShippingMethods($deliveries);
        return $result;
    }
}
