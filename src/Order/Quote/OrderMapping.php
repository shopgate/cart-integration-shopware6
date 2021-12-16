<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote;

use Shopgate\Shopware\System\CustomFields\CustomFieldMapping;
use ShopgateOrder;

class OrderMapping
{
    private CustomFieldMapping $customFieldMapping;

    public function __construct(CustomFieldMapping $customFieldMapping)
    {
        $this->customFieldMapping = $customFieldMapping;
    }

    public function mapIncomingOrder(ShopgateOrder $shopgateOrder): array
    {
        return $this->customFieldMapping->mapToShopwareCustomFields($shopgateOrder);
    }
}
