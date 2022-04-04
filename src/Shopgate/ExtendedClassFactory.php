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
use ShopgateDeliveryNote;
use ShopgateExternalCoupon;
use ShopgateExternalOrderExtraCost;
use ShopgateExternalOrderItem;
use ShopgateExternalOrderTax;
use ShopgateOrder;
use ShopgatePaymentMethod;
use ShopgateShippingMethod;

class ExtendedClassFactory
{
    private ShopgateCart $cart;
    private ShopgateCartItem $cartItem;
    private ShopgateExternalOrderItem $orderItem;
    private ShopgateExternalOrderTax $orderTax;
    private ShopgateExternalCoupon $externalCoupon;
    private ShopgateOrder $order;
    private ShopgateShippingMethod $shippingMethod;
    private ShopgateDeliveryNote $deliveryNote;
    private ShopgateExternalOrderExtraCost $orderExtraCost;
    private ShopgatePaymentMethod $paymentMethod;

    public function __construct(
        ShopgateCart $cart,
        ShopgateCartItem $cartItem,
        ShopgateExternalOrderItem $orderItem,
        ShopgateExternalOrderTax $orderTax,
        ShopgateExternalCoupon $externalCoupon,
        ShopgateOrder $order,
        ShopgateShippingMethod $shippingMethod,
        ShopgateDeliveryNote $deliveryNote,
        ShopgateExternalOrderExtraCost $orderExtraCost,
        ShopgatePaymentMethod $paymentMethod
    ) {
        $this->cart = $cart;
        $this->cartItem = $cartItem;
        $this->orderItem = $orderItem;
        $this->orderTax = $orderTax;
        $this->externalCoupon = $externalCoupon;
        $this->order = $order;
        $this->shippingMethod = $shippingMethod;
        $this->deliveryNote = $deliveryNote;
        $this->orderExtraCost = $orderExtraCost;
        $this->paymentMethod = $paymentMethod;
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

    public function createShippingMethod(): ShopgateShippingMethod
    {
        return clone $this->shippingMethod;
    }

    public function createDeliveryNote(): ShopgateDeliveryNote
    {
        return clone $this->deliveryNote;
    }

    public function createOrderExtraCost(): ShopgateExternalOrderExtraCost
    {
        return clone $this->orderExtraCost;
    }

    public function createPaymentMethod(): ShopgatePaymentMethod
    {
        return clone $this->paymentMethod;
    }
}
