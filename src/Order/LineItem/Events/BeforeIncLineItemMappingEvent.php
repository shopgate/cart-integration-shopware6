<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use ShopgateCartBase;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeIncLineItemMappingEvent extends Event
{
    private ShopgateCartBase $cart;

    public function __construct(ShopgateCartBase $cart)
    {
        $this->cart = $cart;
    }

    public function getCart(): ShopgateCartBase
    {
        return $this->cart;
    }
}
