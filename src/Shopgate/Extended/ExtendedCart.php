<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateCart;
use ShopgateExternalCoupon;
use ShopgateOrderItem;

class ExtendedCart extends ShopgateCart
{
    use CloningTrait;

    public function loadFromShopgateCart(ShopgateCart $cart): ExtendedCart
    {
        $this->dataToEntity($cart->toArray());
        return $this;
    }

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
}
