<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalCoupon;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

class LineItemPromoMapping
{

    /**
     * @param ExtendedExternalCoupon[] $promos
     * @return array
     */
    public function mapIncomingPromos(array $promos): array
    {
        $lineItems = [];
        foreach ($promos as $coupon) {
            // skip adding line item as cart rules are applied automatically
            if ($coupon->getType() === ExtendedExternalCoupon::TYPE_CART_RULE) {
                continue;
            }
            $lineItems[] = [
                'referencedId' => $coupon->getCode(),
                'type' => LineItem::PROMOTION_LINE_ITEM_TYPE
            ];
        }

        return $lineItems;
    }

    /**
     * @param LineItem $lineItem
     * @param ExtendedCart $sgCart
     * @return ExtendedExternalCoupon
     */
    public function mapValidCoupon(LineItem $lineItem, ExtendedCart $sgCart): ExtendedExternalCoupon
    {
        $refId = $lineItem->getReferencedId(); // empty string when automatic cart_rule
        $code = empty($refId) ? $lineItem->getId() : $refId;
        $coupon = $sgCart->findExternalCoupon($code) ?? (new ExtendedExternalCoupon())->setIsNew(true);
        $coupon->setCode($code);
        $coupon->setType(empty($refId) ? ExtendedExternalCoupon::TYPE_CART_RULE : ExtendedExternalCoupon::TYPE_COUPON);
        $coupon->addDecodedInfo(['id' => $lineItem->getId()]);
        $coupon->setCurrency($sgCart->getCurrency());
        $coupon->setIsValid(true);
        $coupon->setName($lineItem->getLabel());
        $coupon->setIsFreeShipping(false);
        if ($lineItem->getPrice()) {
            $coupon->setAmountGross(-($lineItem->getPrice()->getTotalPrice()));
        }
        return $coupon;
    }
}
