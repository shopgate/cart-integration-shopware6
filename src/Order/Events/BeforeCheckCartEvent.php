<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeCheckCartEvent extends Event
{
    private ExtendedCart $extendedCart;

    /**
     * @param ExtendedCart $extendedCart
     */
    public function __construct(ExtendedCart $extendedCart)
    {
        $this->extendedCart = $extendedCart;
    }

    /**
     * @return ExtendedCart
     */
    public function getExtendedCart(): ExtendedCart
    {
        return $this->extendedCart;
    }
}
