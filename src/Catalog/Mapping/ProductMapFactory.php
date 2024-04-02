<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate_Model_AbstractExport;
use Shopgate_Model_Catalog_Product;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;

class ProductMapFactory
{
    public function __construct(
        private readonly Shopgate_Model_AbstractExport $simpleProductMapping,
        private readonly Shopgate_Model_AbstractExport $variantProductMapping
    ) {
    }

    /**
     * @return SimpleProductMapping|ConfigProductMapping|Shopgate_Model_AbstractExport
     */
    public function createMapClass(SalesChannelProductEntity $entity): Shopgate_Model_Catalog_Product
    {
        // empty is a quick check for 0 or null
        return clone(empty($entity->getChildCount()) ? $this->simpleProductMapping : $this->variantProductMapping);
    }
}
