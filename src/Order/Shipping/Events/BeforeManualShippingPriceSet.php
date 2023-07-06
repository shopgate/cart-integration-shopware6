<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping\Events;

use ShopgateCartBase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeManualShippingPriceSet extends Event
{
    public function __construct(private readonly CalculatedPrice $price, private readonly Cart $swCart, private readonly ShopgateCartBase $sgOrder)
    {
    }

    public function getPrice(): CalculatedPrice
    {
        return $this->price;
    }

    public function getSwCart(): Cart
    {
        return $this->swCart;
    }

    public function getSgOrder(): ShopgateCartBase
    {
        return $this->sgOrder;
    }
}
