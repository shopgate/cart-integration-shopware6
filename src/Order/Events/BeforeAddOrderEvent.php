<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use ShopgateOrder;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeAddOrderEvent extends Event
{
    private SalesChannelContext $context;
    private ShopgateOrder $shopgateOrder;

    /**
     * @param SalesChannelContext $context
     * @param ShopgateOrder $shopgateOrder
     */
    public function __construct(SalesChannelContext $context, ShopgateOrder $shopgateOrder)
    {
        $this->context = $context;
        $this->shopgateOrder = $shopgateOrder;
    }

    /**
     * @return SalesChannelContext
     */
    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    /**
     * @return ShopgateOrder
     */
    public function getShopgateOrder(): ShopgateOrder
    {
        return $this->shopgateOrder;
    }
}
