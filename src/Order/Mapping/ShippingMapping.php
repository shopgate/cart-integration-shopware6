<?php

namespace Shopgate\Shopware\Order\Mapping;

use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateShippingMethod;
use Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Symfony\Component\HttpFoundation\Request;

class ShippingMapping
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
     * @return ShopgateShippingMethod[]
     */
    public function mapShippingMethods(): array
    {
        $list = [];
        $initContext = $this->contextManager->getSalesContext();
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
            foreach ($cart->getDeliveries() as $delivery) {
                $method = $delivery->getShippingMethod();
                $exportShipping = new ShopgateShippingMethod();
                $exportShipping->setId($method->getId());
                $exportShipping->setTitle($method->getName());
                $exportShipping->setDescription($method->getDescription());
                $exportShipping->setAmountWithTax($delivery->getShippingCosts()->getTotalPrice());
                $exportShipping->setShippingGroup('SHOPGATE');
                $list[$method->getId()] = $exportShipping;
            }
        }

        return $list;
    }
}
