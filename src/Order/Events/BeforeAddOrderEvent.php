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

    public function __construct(ShopgateOrder $shopgateOrder, SalesChannelContext $context)
    {
        $this->shopgateOrder = $shopgateOrder;
        $this->context = $context;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getShopgateOrder(): ShopgateOrder
    {
        return $this->shopgateOrder;
    }
}
