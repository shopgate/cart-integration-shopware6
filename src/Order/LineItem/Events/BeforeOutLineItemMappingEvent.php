<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopware\Core\Checkout\Cart\Cart;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeOutLineItemMappingEvent extends Event
{
    private Cart $cart;
    private ExtendedCart $shopgateCart;

    public function __construct(Cart $cart, ExtendedCart $shopgateCart)
    {
        $this->cart = $cart;
        $this->shopgateCart = $shopgateCart;
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
