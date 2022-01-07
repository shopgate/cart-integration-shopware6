<?php

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCartItem;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Checkout\Cart\Price\CashRounding;

class ExtendedClassFactory
{
    private CashRounding $rounding;
    private ContextManager $contextManager;

    public function __construct(CashRounding $rounding, ContextManager $contextManager)
    {
        $this->rounding = $rounding;
        $this->contextManager = $contextManager;
    }

    public function createCartItem(): ExtendedCartItem
    {
        return new ExtendedCartItem($this->rounding, $this->contextManager);
    }
}
