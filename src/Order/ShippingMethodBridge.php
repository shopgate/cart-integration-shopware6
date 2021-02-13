<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Symfony\Component\HttpFoundation\Request;

class ShippingMethodBridge
{
    /** @var ContextSwitchRoute */
    private $contextSwitchRoute;
    /** @var ContextManager */
    private $contextManager;
    /** @var ShippingMethodRoute */
    private $shippingMethodRoute;
    /** @var CheckoutCartPageLoader */
    private $cartPageLoader;

    /**
     * @param ContextSwitchRoute $contextSwitchRoute
     * @param ContextManager $contextManager
     * @param ShippingMethodRoute $shippingMethodRoute
     * @param CheckoutCartPageLoader $cartPageLoader
     */
    public function __construct(
        ContextSwitchRoute $contextSwitchRoute,
        ContextManager $contextManager,
        ShippingMethodRoute $shippingMethodRoute,
        CheckoutCartPageLoader $cartPageLoader
    ) {
        $this->contextSwitchRoute = $contextSwitchRoute;
        $this->contextManager = $contextManager;
        $this->shippingMethodRoute = $shippingMethodRoute;
        $this->cartPageLoader = $cartPageLoader;
    }

    /**
     * @param SalesChannelContext $initContext
     * @return DeliveryCollection
     */
    public function getCalculatedDeliveries(SalesChannelContext $initContext): DeliveryCollection
    {
        $list = [];
        $shippingMethods = $this->shippingMethodRoute->load(
            new Request(['onlyAvailable' => true]),
            $initContext
        )->getShippingMethods();
        foreach ($shippingMethods as $shipMethod) {
            $dataBag = new RequestDataBag(
                [SalesChannelContextService::SHIPPING_METHOD_ID => $shipMethod->getId()]
            );
            $token = $this->contextSwitchRoute->switchContext($dataBag, $initContext)->getToken();
            $context = $this->contextManager->loadByCustomerToken($token);
            $cart = $this->cartPageLoader->load(new Request(), $context)->getCart();
            foreach ($cart->getDeliveries()->getElements() as $delivery) {
                $list[$delivery->getShippingMethod()->getId()] = $delivery;
            }
        }

        return new DeliveryCollection($list);
    }
}
