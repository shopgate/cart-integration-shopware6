<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateOrderItem;

class ExtendedOrderItem extends ShopgateOrderItem
{
    use CloningTrait;
    use SerializerTrait;
}
