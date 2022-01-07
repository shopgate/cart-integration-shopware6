<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCartItem;
use ShopgateOrderItem;
use Shopware\Core\Checkout\Cart\Price\CashRounding;

class ExtendedCartItem extends ShopgateCartItem
{
    use CloningTrait;

    private CashRounding $rounding;
    private ContextManager $contextManager;

    public function __construct(CashRounding $rounding, ContextManager $contextManager)
    {
        parent::__construct([]);
        $this->rounding = $rounding;
        $this->contextManager = $contextManager;
    }

    public function transformFromOrderItem(ShopgateOrderItem $orderItem): ExtendedCartItem
    {
        return $this->dataToEntity($orderItem->toArray());
    }

    /**
     * @throws MissingContextException
     */
    public function setUnitAmount($value): void
    {
        parent::setUnitAmount($this->rounding->cashRound(
            (float)$value,
            $this->contextManager->getSalesContext()->getItemRounding()
        ));
    }

    /**
     * @throws MissingContextException
     */
    public function setUnitAmountWithTax($value): void
    {
        parent::setUnitAmountWithTax($this->rounding->cashRound(
            (float)$value,
            $this->contextManager->getSalesContext()->getItemRounding()
        ));
    }

    public function setStockQuantity($value): void
    {
        parent::setStockQuantity((int)$value);
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }
}
