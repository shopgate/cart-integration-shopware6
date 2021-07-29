<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateExternalCoupon;

class ExtendedExternalCoupon extends ShopgateExternalCoupon
{
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
}
