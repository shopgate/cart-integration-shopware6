<?php

namespace Shopgate\Shopware\Export\Catalog\Mapping;

use Shopgate_Model_Catalog_Attribute;

class ChildProductMapping extends SimpleProductMapping
{
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
}
