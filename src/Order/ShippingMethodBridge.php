<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethod;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class ShippingMethodBridge
{
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
        ContextManager $contextManager,
        ShippingMethodRoute $shippingMethodRoute,
        CheckoutCartPageLoader $cartPageLoader
    ) {
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
            $initContext,
            (new Criteria())->addFilter(
                new NotFilter(NotFilter::CONNECTION_OR,
                    [new EqualsAnyFilter('id', [GenericShippingMethod::UUID, FreeShippingMethod::UUID])]
                )
            )
        )->getShippingMethods();
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
