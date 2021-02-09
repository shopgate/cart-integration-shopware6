<?php

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateCart;
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
        return $foundItems ? $foundItems[0] : null;
    }
}
