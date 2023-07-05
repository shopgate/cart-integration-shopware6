<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopware\Core\Checkout\Cart\Cart;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeOutLineItemMappingEvent extends Event
{
    public function __construct(private readonly Cart $cart, private readonly ExtendedCart $shopgateCart)
    {
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getShopgateCart(): ExtendedCart
    {
        return $this->shopgateCart;
    }
}
