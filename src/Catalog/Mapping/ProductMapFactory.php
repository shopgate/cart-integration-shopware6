<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopware\Core\Content\Product\ProductEntity;

class ProductMapFactory
{
    private SimpleProductMapping $simpleProductMapping;
    private ConfigProductMapping $configProductMapping;

    /**
     * @param SimpleProductMapping $simpleProductMapping
     * @param ConfigProductMapping $configProductMapping
     */
    public function __construct(SimpleProductMapping $simpleProductMapping, ConfigProductMapping $configProductMapping)
    {
        $this->simpleProductMapping = $simpleProductMapping;
        $this->configProductMapping = $configProductMapping;
    }

    /**
     * @param ProductEntity $entity
     * @return SimpleProductMapping|ConfigProductMapping
     */
    public function createMapClass(ProductEntity $entity)
    {
        /** @noinspection IsEmptyFunctionUsageInspection */
        if (empty($entity->getChildCount())) {
            $product = clone $this->simpleProductMapping;
        } else {
            $product = clone $this->configProductMapping;
        }
        return $product;
    }
}
