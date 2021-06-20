<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events\LineItem;

use ShopgateCartBase;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeIncLineItemMappingEvent extends Event
{
    private ShopgateCartBase $cart;

    /**
     * @param ShopgateCartBase $cart
     */
    public function __construct(ShopgateCartBase $cart)
    {
        $this->cart = $cart;
    }

    /**
     * @return ShopgateCartBase
     */
    public function getCart(): ShopgateCartBase
    {
        return $this->cart;
    }
}
