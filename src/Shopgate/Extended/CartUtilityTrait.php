<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateExternalCoupon;
use ShopgateOrderItem;
use ShopgateShippingInfo;

/**
 * Common functions for both cart and order objects
 */
trait CartUtilityTrait
{
    /**
     * Locates item by ID
     *
     * @param string $itemId
     * @return ShopgateOrderItem|null
     */
    public function findItemById(string $itemId): ?ShopgateOrderItem
    {
        $foundItems = array_filter(
            $this->items,
            static function (ShopgateOrderItem $item) use ($itemId) {
                return $item->getItemNumber() === $itemId;
            }
        );
        return $foundItems ? array_pop($foundItems) : null;
    }

    /**
     * @param string $code
     * @return ShopgateExternalCoupon|null
     */
    public function findExternalCoupon(string $code): ?ShopgateExternalCoupon
    {
        $foundItems = array_filter(
            $this->external_coupons,
            static function (ShopgateExternalCoupon $coupon) use ($code) {
                return $coupon->getCode() === $code;
            }
        );
        return $foundItems ? array_pop($foundItems) : null;
    }

    /**
     * @return bool
     */
    public function isShippingFree(): bool
    {
        return $this->getShippingCost() === 0.0;
    }

    /**
     * @return float
     */
    public function getShippingCost(): float
    {
        $cost = 0.0;
        if ($this->getAmountShipping() || ($this->getShippingInfos() instanceof ShopgateShippingInfo)) {
            $cost = (float)($this->getAmountShipping() ?? $this->getShippingInfos()->getAmountNet());
        }
        return $cost;
    }
}
