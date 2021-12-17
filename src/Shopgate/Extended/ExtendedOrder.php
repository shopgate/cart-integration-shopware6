<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateOrder;

class ExtendedOrder extends ShopgateOrder
{
    use CloningTrait;
    use CartUtilityTrait;

    /**
     * @param ShopgateOrder $order
     * @return $this
     */
    public function loadFromShopgateOrder(ShopgateOrder $order): ExtendedOrder
    {
        $visitor = new ExtendedToArrayVisitor();
        $visitor->visitContainer($order);
        $this->dataToEntity($visitor->getArray());

        return $this;
    }
}
