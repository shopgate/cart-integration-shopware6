<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use ShopgateCartBase;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeIncLineItemMappingEvent extends Event
{
    public function __construct(private readonly ShopgateCartBase $cart)
    {
    }

    public function getCart(): ShopgateCartBase
    {
        return $this->cart;
    }
}
