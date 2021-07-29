<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateCart;
use ShopgateExternalCoupon;

class ExtendedCart extends ShopgateCart
{
    use CloningTrait;
    use CartUtilityTrait;

    /**
     * @param ShopgateCart $cart
     * @return $this
     */
    public function loadFromShopgateCart(ShopgateCart $cart): ExtendedCart
    {
        $this->dataToEntity($cart->toArray());
        return $this;
    }

    /**
     * Make all coupons not valid
     *
     * @return ExtendedCart
     */
    public function invalidateCoupons(): ExtendedCart
    {
        array_map(static function (ShopgateExternalCoupon $coupon) {
            $coupon->setIsValid(false);
        }, $this->external_coupons);

        return $this;
    }

    /**
     * Rewriting to use custom external coupon class
     *
     * @param ShopgateExternalCoupon[]|array $value
     */
    public function setExternalCoupons($value): void
    {
        if (!is_array($value)) {
            $this->external_coupons = null;

            return;
        }

        foreach ($value as $index => &$element) {
            if ((!is_object($element) || !($element instanceof ShopgateExternalCoupon)) && !is_array($element)) {
                unset($value[$index]);
                continue;
            }

            if (is_array($element)) {
                $element = new ExtendedExternalCoupon($element);
            }
        }

        // safety
        unset($element);

        $this->external_coupons = $value;
    }
}
