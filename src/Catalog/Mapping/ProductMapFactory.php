<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate_Model_AbstractExport;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;

class ProductMapFactory
{
    public function __construct(private readonly Shopgate_Model_AbstractExport $simpleProductMapping, private readonly Shopgate_Model_AbstractExport $variantProductMapping)
    {
    }

    public function createMapClass(SalesChannelProductEntity $entity): Shopgate_Model_AbstractExport
    {
        // empty is a quick check for 0 or null
        return clone(empty($entity->getChildCount()) ? $this->simpleProductMapping : $this->variantProductMapping);
    }
}
