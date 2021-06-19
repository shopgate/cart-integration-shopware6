<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use ShopgateCartBase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeLineItemMappingEvent extends Event
{
    private Cart $cart;
    private ExtendedCart $shopgateCart;

    /**
     * @param Cart $cart
     * @param ShopgateCartBase $shopgateCart
     */
    public function __construct(Cart $cart, ExtendedCart $shopgateCart)
    {
        $this->cart = $cart;
        $this->shopgateCart = $shopgateCart;
    }

    /**
     * @return DataBag
     */
    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * @return ExtendedCart
     */
    public function getShopgateCart(): ExtendedCart
    {
        return $this->shopgateCart;
    }
}
