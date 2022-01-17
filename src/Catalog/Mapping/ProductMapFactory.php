<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate_Model_AbstractExport;
use Shopware\Core\Content\Product\ProductEntity;

class ProductMapFactory
{
    private Shopgate_Model_AbstractExport $simpleProductMapping;
    private Shopgate_Model_AbstractExport $variantProductMapping;

    public function __construct(
        Shopgate_Model_AbstractExport $simpleProductMapping,
        Shopgate_Model_AbstractExport $configProductMapping
    ) {
        $this->simpleProductMapping = $simpleProductMapping;
        $this->variantProductMapping = $configProductMapping;
    }

    /**
     * @return SimpleProductMapping|ConfigProductMapping
     */
    public function createMapClass(ProductEntity $entity): Shopgate_Model_AbstractExport
    {
        // empty is a quick check for 0 or null
        /** @noinspection IsEmptyFunctionUsageInspection */
        if (empty($entity->getChildCount())) {
            $product = clone $this->simpleProductMapping;
        } else {
            $product = clone $this->variantProductMapping;
        }
        return $product;
    }
}
