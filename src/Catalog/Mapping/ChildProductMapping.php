<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_Attribute;

class ChildProductMapping extends SimpleProductMapping
{

    /**
     * @param ContextManager $contextManager
     * @param SortTree $sortTree
     */
    public function __construct(ContextManager $contextManager, SortTree $sortTree)
    {
        parent::__construct($contextManager, $sortTree);
        $this->fireMethods[] = 'setAttributes';
        $this->fireMethods[] = 'setIsDefaultChild';
    }

    public function setAttributes(): void
    {
        if (null === $this->item->getParentId()) {
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
