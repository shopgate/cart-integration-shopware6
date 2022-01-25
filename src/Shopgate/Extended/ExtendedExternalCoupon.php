<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateExternalCoupon;
use Shopware\Core\Checkout\Cart\Price\CashRounding;

class ExtendedExternalCoupon extends ShopgateExternalCoupon
{
    use SerializerTrait;

    /**
     * Identifier placed inside the coupon internal field to identify
     * cart rules from actual coupons
     */
    public const TYPE_COUPON = 'coupon';
    public const TYPE_CART_RULE = 'cartRule';
    private CashRounding $rounding;
    private ContextManager $contextManager;
    /**
     * Whether this coupon is NOT also present in the incoming
     * cart object's external_coupons. Meaning we just created it.
     *
     * @var bool
     */
    protected bool $isNew = false;

    public function __construct(CashRounding $rounding, ContextManager $contextManager)
    {
        parent::__construct([]);
        $this->rounding = $rounding;
        $this->contextManager = $contextManager;
    }

    public function setAmountGross($value): void
    {
        parent::setAmountGross(null !== $value ? $this->rounding->cashRound(
            (float)$value,
            $this->contextManager->getSalesContext()->getItemRounding()
        ) : null);
    }

    public function setAmountNet($value): void
    {
        parent::setAmountNet(null !== $value ? $this->rounding->cashRound(
            (float)$value,
            $this->contextManager->getSalesContext()->getItemRounding()
        ) : null);
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * @param bool $isNew
     * @return self
     */
    public function setIsNew(bool $isNew): self
    {
        $this->isNew = $isNew;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->decodedInfo['itemType'] ?? '';
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->decodedInfo['itemType'] = $type;

        return $this;
    }
}
