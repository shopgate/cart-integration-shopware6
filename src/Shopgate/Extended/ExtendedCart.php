<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateCart;
use ShopgateExternalCoupon;
use ShopgateOrderItem;

class ExtendedCart extends ShopgateCart
{
    use CloningTrait;
    use CartUtilityTrait;

    protected ExtendedExternalCoupon|ShopgateExternalCoupon $externalCoupon;
    protected ExtendedOrderItem|ShopgateOrderItem $orderItem;

    public function __construct(ExtendedExternalCoupon|ShopgateExternalCoupon $extendedExternalCoupon, ExtendedOrderItem|ShopgateOrderItem $extendedOrderItem)
    {
        parent::__construct([]);
        $this->externalCoupon = $extendedExternalCoupon;
        $this->orderItem = $extendedOrderItem;
    }

    public function loadFromShopgateCart(ShopgateCart $cart): ExtendedCart
    {
        $this->dataToEntity($cart->toArray());

        return $this;
    }

    /**
     * Make all coupons not valid
     */
    public function invalidateCoupons(): ExtendedCart
    {
        array_map(static function (ShopgateExternalCoupon $coupon) {
            $coupon->setIsValid(false);
        }, $this->external_coupons);

        return $this;
    }
}
