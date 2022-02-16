<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Catalog\Product\Property\CustomFieldBridge;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_Catalog_Attribute;

class ChildProductMapping extends SimpleProductMapping
{

    /**
     * @param ContextManager $contextManager
     * @param CustomFieldBridge $customFieldSetBridge
     * @param SortTree $sortTree
     * @param TierPriceMapping $tierPriceMapping
     * @param Formatter $translation
     */
    public function __construct(
        ContextManager $contextManager,
        CustomFieldBridge $customFieldSetBridge,
        SortTree $sortTree,
        TierPriceMapping $tierPriceMapping,
        Formatter $translation
    ) {
        parent::__construct($contextManager, $customFieldSetBridge, $sortTree, $tierPriceMapping, $translation);
        $this->fireMethods[] = 'setAttributes';
        $this->fireMethods[] = 'setIsDefaultChild';
    }

    public function setAttributes(): void
    {
        if (null === $this->item->getParentId()) {
            parent::setAttributes([]);
            return;
        }
        $export = [];
        foreach ($this->item->getOptions() as $option) {
            $itemAttribute = new Shopgate_Model_Catalog_Attribute();
            $itemAttribute->setGroupUid($option->getGroupId());
            $itemAttribute->setLabel($option->getName());
            $export[] = $itemAttribute;
        }
        parent::setAttributes($export);
    }

    public function getIsChild(): bool
    {
        return true;
    }

    public function setIsDefaultChild(): void
    {
        parent::setIsDefaultChild($this->item->getMainVariantId() === $this->item->getId());
    }
}
