<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use ShopgateOrderItem;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event happens after the incoming Shopgate item is mapped
 * to an SW compatible line item array
 */
class AfterIncItemMappingEvent extends Event
{
    private DataBag $mapping;
    private ShopgateOrderItem $item;

    public function __construct(DataBag $mapping, ShopgateOrderItem $item)
    {
        $this->mapping = $mapping;
        $this->item = $item;
    }

    /**
     * Represents the array that will be added
     * as an SW line item to SW cart
     */
    public function getMapping(): DataBag
    {
        return $this->mapping;
    }

    public function getItem(): ShopgateOrderItem
    {
        return $this->item;
    }
}
