<?php

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCartItem;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalCoupon;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderItem;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderTax;

class ExtendedClassFactory
{
    private ExtendedCartItem $extendedCartItem;
    private ExtendedExternalOrderItem $externalOrderItem;
    private ExtendedExternalOrderTax $externalOrderTax;
    private ExtendedExternalCoupon $extendedExternalCoupon;

    public function __construct(
        ExtendedCartItem $extendedCartItem,
        ExtendedExternalOrderItem $externalOrderItem,
        ExtendedExternalOrderTax $externalOrderTax,
        ExtendedExternalCoupon $extendedExternalCoupon
    ) {
        $this->extendedCartItem = $extendedCartItem;
        $this->externalOrderItem = $externalOrderItem;
        $this->externalOrderTax = $externalOrderTax;
        $this->extendedExternalCoupon = $extendedExternalCoupon;
    }

    public function createCartItem(): ExtendedCartItem
    {
        return clone $this->extendedCartItem;
    }

    public function createOrderLineItem(): ExtendedExternalOrderItem
    {
        return clone $this->externalOrderItem;
    }

    public function createExternalOrderTax(): ExtendedExternalOrderTax
    {
        return clone $this->externalOrderTax;
    }

    public function createExternalCoupon(): ExtendedExternalCoupon
    {
        return clone $this->extendedExternalCoupon;
    }
}
