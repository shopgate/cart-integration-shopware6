<?php

namespace Shopgate\Shopware\Export\Catalog\Mapping;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\Catalog\Products\ProductProperties;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_AttributeGroup;
use Shopgate_Model_Catalog_Product;

class ConfigProductMapping extends SimpleProductMapping
{
    /** @var ContextManager */
    protected $contextManager;
    /** @var ProductProperties */
    private $productProperties;

    /**
     * @param ContextManager $contextManager
     * @param int $sortOrder
     * @param ProductProperties $productProperties
     */
    public function __construct(ContextManager $contextManager, int $sortOrder, ProductProperties $productProperties)
    {
        parent::__construct($contextManager, $sortOrder);
        $this->productProperties = $productProperties;
    }

    /**
     * @throws MissingContextException
     */
    public function setAttributeGroups(): void
    {
        /** @noinspection NullPointerExceptionInspection */
        $optionIds = $this->item->getChildren()->first()->getOptions()->getPropertyGroupIds();
        $optionGroups = $this->productProperties->getGroupOptions($optionIds);
        $result = [];
        foreach ($optionGroups as $optionGroup) {
            $attributeGroup = new Shopgate_Model_Catalog_AttributeGroup();
            $attributeGroup->setUid($optionGroup->getId());
            $attributeGroup->setLabel($optionGroup->getName());
            $result[] = $attributeGroup;
        }
        parent::setAttributeGroups($result);
    }

    public function setDisplayType(): void
    {
        parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_SELECT);
    }

    public function setChildren(): void
    {
        $result = [];
        $children = $this->item->getChildren();
        foreach ($children as $child) {
            $exportChild = new ChildProductMapping($this->contextManager, 0);
            $exportChild->setItem($child);
            $exportChild->setIsChild(true);
            $result[] = $exportChild->generateData();
        }
        parent::setChildren($result);
    }
}
