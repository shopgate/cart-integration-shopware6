<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use ShopgateOrder;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeAddOrderEvent extends Event
{
    public function __construct(
        private readonly ShopgateOrder $shopgateOrder,
        private readonly SalesChannelContext $context
    ) {
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
