<?php

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCartItem;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalCoupon;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderItem;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderTax;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use ShopgateCart;
use ShopgateCartItem;
use ShopgateExternalCoupon;
use ShopgateExternalOrderItem;
use ShopgateExternalOrderTax;
use ShopgateOrder;

class ExtendedClassFactory
{
    private ShopgateCart $cart;
    private ShopgateCartItem $cartItem;
    private ShopgateExternalOrderItem $orderItem;
    private ShopgateExternalOrderTax $orderTax;
    private ShopgateExternalCoupon $externalCoupon;
    private ShopgateOrder $order;

    public function __construct(
        ShopgateCart $cart,
        ShopgateCartItem $cartItem,
        ShopgateExternalOrderItem $orderItem,
        ShopgateExternalOrderTax $orderTax,
        ShopgateExternalCoupon $externalCoupon,
        ShopgateOrder $order
    ) {
        $this->cart = $cart;
        $this->cartItem = $cartItem;
        $this->orderItem = $orderItem;
        $this->orderTax = $orderTax;
        $this->externalCoupon = $externalCoupon;
        $this->order = $order;
    }

    /**
     * @return ExtendedCartItem|ShopgateCartItem
     */
    public function createCartItem(): ShopgateCartItem
    {
        return clone $this->cartItem;
    }

    /**
     * @return ExtendedExternalOrderItem|ShopgateExternalOrderItem
     */
    public function createOrderLineItem(): ShopgateExternalOrderItem
    {
        return clone $this->orderItem;
    }

    /**
     * @return ExtendedExternalOrderTax|ShopgateExternalOrderTax
     */
    public function createExternalOrderTax(): ShopgateExternalOrderTax
    {
        return clone $this->orderTax;
    }

    /**
     * @return ExtendedExternalCoupon|ShopgateExternalCoupon
     */
    public function createExternalCoupon(): ShopgateExternalCoupon
    {
        return clone $this->externalCoupon;
    }

    /**
     * @return ExtendedCart|ShopgateCart
     */
    public function createCart(): ShopgateCart
    {
        return clone $this->cart;
    }

    /**
     * @return ExtendedOrder|ShopgateOrder
     */
    public function createOrder(): ShopgateOrder
    {
        return clone $this->order;
    }
}
