<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopware\Core\Content\Product\ProductEntity;

class ProductMapFactory
{
    /** @var SimpleProductMapping */
    private $simpleProductMapping;
    /** @var ConfigProductMapping */
    private $configProductMapping;

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
     * @param int $sortPosition - todo: implement via cache and use DI instead
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