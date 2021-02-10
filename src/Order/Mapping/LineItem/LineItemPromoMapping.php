<?php

namespace Shopgate\Shopware\Order\Mapping\LineItem;

use ShopgateExternalCoupon;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

class LineItemPromoMapping
{
    /**
     * @param ShopgateExternalCoupon[] $promos
     * @return array
     */
    public function mapIncomingPromos(array $promos): array
    {
        $lineItems = [];
        foreach ($promos as $coupon) {
            $lineItems[] = [
                'referencedId' => $coupon->getCode(),
                'type' => LineItem::PROMOTION_LINE_ITEM_TYPE
            ];
        }

        return $lineItems;
    }

    /**
     * @param LineItem $lineItem
     * @param ShopgateExternalCoupon $coupon
     * @return ShopgateExternalCoupon
     */
    public function mapValidCoupon(LineItem $lineItem, ShopgateExternalCoupon $coupon): ShopgateExternalCoupon
    {
        $coupon->setIsValid(true);
        $coupon->setName($lineItem->getLabel());
        $coupon->setIsFreeShipping(false);
        if ($lineItem->getPrice()) {
            $coupon->setAmountGross(-($lineItem->getPrice()->getTotalPrice()));
        }
        return $coupon;
    }
}
