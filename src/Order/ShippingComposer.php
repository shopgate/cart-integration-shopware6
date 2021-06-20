<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
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

    /**
     * @param ShippingMethodBridge $shippingBridge
     * @param CheckoutCartPageLoader $cartPageLoader
     * @param ContextManager $contextManager
     */
    public function __construct(
        ShippingMethodBridge $shippingBridge,
        CheckoutCartPageLoader $cartPageLoader,
        ContextManager $contextManager
    ) {
        $this->shippingBridge = $shippingBridge;
        $this->cartPageLoader = $cartPageLoader;
        $this->contextManager = $contextManager;
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
}
