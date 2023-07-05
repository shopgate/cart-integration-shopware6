<?php declare(strict_types=1);

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

    public function __construct(private readonly DataBag $mapping, private readonly ShopgateOrderItem $item, private readonly SalesChannelContext $context)
    {
    }

    /**
     * Represents the array that will be added
     * as an SW line item to SW cart
     */
    public function getMapping(): DataBag
    {
        return $this->mapping;
    }

    public function getItem(): ShopgateOrderItem|ExtendedOrderItem
    {
        return $this->item;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
