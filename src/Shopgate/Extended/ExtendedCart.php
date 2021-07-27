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
}
