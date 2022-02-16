<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Catalog\Product\Property\CustomFieldBridge;
use Shopgate\Shopware\Catalog\Product\Property\PropertyBridge;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_AbstractExport;
use Shopgate_Model_Catalog_AttributeGroup;
use Shopgate_Model_Catalog_Product;

class ConfigProductMapping extends SimpleProductMapping
{
    protected ContextManager $contextManager;
    protected SortTree $sortTree;
    private PropertyBridge $productProperties;
    private Shopgate_Model_AbstractExport $childProductMapping;

    public function __construct(
        ContextManager $contextManager,
        CustomFieldBridge $customFieldSetBridge,
        SortTree $sortTree,
        TierPriceMapping $tierPriceMapping,
        Formatter $translation,
        PropertyBridge $productProperties,
        Shopgate_Model_AbstractExport $childProductMapping
    ) {
        parent::__construct($contextManager, $customFieldSetBridge, $sortTree, $tierPriceMapping, $translation);
        $this->productProperties = $productProperties;
        $this->childProductMapping = $childProductMapping;
    }

    /**
     * @throws MissingContextException
     */
    public function setAttributeGroups(): void
    {
        /** @noinspection NullPointerExceptionInspection */
        if (($first = $this->item->getChildren()->first()) && $first->getOptions()) {
            $optionIds = $first->getOptions()->getPropertyGroupIds();
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
    }

    public function setDisplayType(): void
    {
        parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_SELECT);
    }

    public function setChildren(): void
    {
        $result = [];
        $children = $this->item->getChildren() ?: [];
        foreach ($children as $child) {
            $exportChild = clone $this->childProductMapping;
            $exportChild->setItem($child);
            $result[] = $exportChild->generateData();
        }
        parent::setChildren($result);
    }
}
