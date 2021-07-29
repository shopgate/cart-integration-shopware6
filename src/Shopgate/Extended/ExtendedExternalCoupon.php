<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateExternalCoupon;

class ExtendedExternalCoupon extends ShopgateExternalCoupon
{
    use SerializerTrait;

    /**
     * Identifier placed inside the coupon internal field to identify
     * cart rules from actual coupons
     */
    public const TYPE_COUPON = 'coupon';
    public const TYPE_CART_RULE = 'cartRule';

    /**
     * Whether this coupon is NOT also present in the incoming
     * cart object's external_coupons. Meaning we just created it.
     *
     * @var bool
     */
    protected bool $isNew = false;

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
