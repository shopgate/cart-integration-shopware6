<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Catalog\Product\Property\PropertyBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Product\ProductEntity;

class ProductMapFactory
{
    /** @var ContextManager */
    private $contextManager;
    /** @var PropertyBridge */
    private $productProperties;

    public function __construct(ContextManager $contextManager, PropertyBridge $productProperties)
    {
        $this->contextManager = $contextManager;
        $this->productProperties = $productProperties;
    }

    /**
     * @param ProductEntity $entity
     * @param int $sortPosition - todo: implement via cache and use DI instead
     * @return SimpleProductMapping|ConfigProductMapping
     */
    public function createMapClass(ProductEntity $entity, int $sortPosition)
    {
        /** @noinspection IsEmptyFunctionUsageInspection */
        if (empty($entity->getChildCount())) {
            $product = new SimpleProductMapping($this->contextManager, $sortPosition);
        } else {
            $product = new ConfigProductMapping($this->contextManager, $sortPosition, $this->productProperties);
        }
        return $product;
    }
}
