<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethod;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Symfony\Component\HttpFoundation\Request;

class ShippingMethodBridge
{
    private AbstractShippingMethodRoute $shippingMethodRoute;

    /**
     * @param ContextManager $contextManager
     * @param AbstractShippingMethodRoute $shippingMethodRoute
     * @param CheckoutCartPageLoader $cartPageLoader
     */
    public function __construct(AbstractShippingMethodRoute $shippingMethodRoute)
    {
        $this->shippingMethodRoute = $shippingMethodRoute;
    }

    /**
     * @param SalesChannelContext $initContext
     * @return ShippingMethodCollection
     */
    public function getDeliveries(SalesChannelContext $initContext): ShippingMethodCollection
    {
        return $this->shippingMethodRoute->load(
            new Request(['onlyAvailable' => true]),
            $initContext,
            (new Criteria())->addFilter(
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [new EqualsAnyFilter('id', [GenericShippingMethod::UUID, FreeShippingMethod::UUID])]
                )
            )
        )->getShippingMethods();
    }
}
