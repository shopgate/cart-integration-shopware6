<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateCart;

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
}
