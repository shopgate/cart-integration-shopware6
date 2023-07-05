<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateExternalOrderItem;
use Shopware\Core\Checkout\Cart\Price\CashRounding;

class ExtendedExternalOrderItem extends ShopgateExternalOrderItem
{

    public function __construct(private readonly CashRounding $rounding, private readonly ContextManager $contextManager)
    {
        parent::__construct();
    }

    public function setUnitAmount($value): void
    {
        parent::setUnitAmount(null !== $value ? $this->rounding->cashRound(
            (float)$value,
            $this->contextManager->getSalesContext()->getItemRounding()
        ) : null);
    }

    public function setUnitAmountWithTax($value): void
    {
        parent::setUnitAmountWithTax(null !== $value ? $this->rounding->cashRound(
            (float)$value,
            $this->contextManager->getSalesContext()->getItemRounding()
        ) : null);
    }
}
