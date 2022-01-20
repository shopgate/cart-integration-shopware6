<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateExternalOrderItem;
use Shopware\Core\Checkout\Cart\Price\CashRounding;

class ExtendedExternalOrderItem extends ShopgateExternalOrderItem
{
    private CashRounding $rounding;
    private ContextManager $contextManager;

    public function __construct(CashRounding $rounding, ContextManager $contextManager)
    {
        parent::__construct([]);
        $this->rounding = $rounding;
        $this->contextManager = $contextManager;
    }

    public function setUnitAmount($value): void
    {
        parent::setUnitAmount($this->rounding->cashRound(
            (float)$value,
            $this->contextManager->getSalesContext()->getItemRounding()
        ));
    }

    public function setUnitAmountWithTax($value): void
    {
        parent::setUnitAmountWithTax($this->rounding->cashRound(
            (float)$value,
            $this->contextManager->getSalesContext()->getItemRounding()
        ));
    }
}
