<?php

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCartItem;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderItem;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderTax;

class ExtendedClassFactory
{
    private ExtendedCartItem $extendedCartItem;
    private ExtendedExternalOrderItem $externalOrderItem;
    private ExtendedExternalOrderTax $externalOrderTax;

    public function __construct(
        ExtendedCartItem $extendedCartItem,
        ExtendedExternalOrderItem $externalOrderItem,
        ExtendedExternalOrderTax $externalOrderTax
    ) {
        $this->extendedCartItem = $extendedCartItem;
        $this->externalOrderItem = $externalOrderItem;
        $this->externalOrderTax = $externalOrderTax;
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
}
