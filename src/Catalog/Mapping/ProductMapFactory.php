<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate_Model_Catalog_Product;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;

readonly class ProductMapFactory
{
    public function __construct(private Shopgate_Model_Catalog_Product $simpleProductMapping, private Shopgate_Model_Catalog_Product $variantProductMapping)
    {
    }

    public function createMapClass(SalesChannelProductEntity $entity): Shopgate_Model_Catalog_Product
    {
        // empty is a quick check for 0 or null
        return clone(empty($entity->getChildCount()) ? $this->simpleProductMapping : $this->variantProductMapping);
    }
}
