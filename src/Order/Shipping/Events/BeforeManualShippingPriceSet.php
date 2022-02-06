<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeManualShippingPriceSet extends Event
{
    private CalculatedPrice $price;
    private Cart $swCart;
    private ExtendedOrder $sgOrder;

    public function __construct(CalculatedPrice $price, Cart $swCart, ExtendedOrder $sgOrder)
    {
        $this->price = $price;
        $this->swCart = $swCart;
        $this->sgOrder = $sgOrder;
    }

    public function getPrice(): CalculatedPrice
    {
        return $this->price;
    }

    public function getSwCart(): Cart
    {
        return $this->swCart;
    }

    public function getSgOrder(): ExtendedOrder
    {
        return $this->sgOrder;
    }
}
