<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateCartItem;
use ShopgateOrderItem;

class ExtendedCartItem extends ShopgateCartItem
{
    use CloningTrait;

    /**
     * @param ShopgateOrderItem $orderItem
     * @return ExtendedCartItem
     */
    public function transformFromOrderItem(ShopgateOrderItem $orderItem): ExtendedCartItem
    {
        return $this->dataToEntity($orderItem->toArray());
    }
}
