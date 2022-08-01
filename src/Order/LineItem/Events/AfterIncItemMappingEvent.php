<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedOrderItem;
use ShopgateOrderItem;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event happens after the incoming Shopgate item is mapped
 * to an SW compatible line item array
 */
class AfterIncItemMappingEvent extends Event
{
    /** Can be set in data bag to skip the item import */
    public const SKIP = 'skip';
    private DataBag $mapping;
    private ShopgateOrderItem $item;
    private SalesChannelContext $context;

    public function __construct(DataBag $mapping, ShopgateOrderItem $item, SalesChannelContext $context)
    {
        $this->mapping = $mapping;
        $this->item = $item;
        $this->context = $context;
    }

    /**
     * Represents the array that will be added
     * as an SW line item to SW cart
     */
    public function getMapping(): DataBag
    {
        return $this->mapping;
    }

    /**
     * @return ShopgateOrderItem|ExtendedOrderItem
     */
    public function getItem(): ShopgateOrderItem
    {
        return $this->item;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
