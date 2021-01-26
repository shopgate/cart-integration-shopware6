<?php

namespace Shopgate\Shopware\Export\Catalog\Mapping;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_Attribute;

class ChildProductMapping extends SimpleProductMapping
{

    /**
     * @param ContextManager $contextManager
     * @param int $sortOrder
     */
    public function __construct(ContextManager $contextManager, int $sortOrder)
    {
        parent::__construct($contextManager, $sortOrder);
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
