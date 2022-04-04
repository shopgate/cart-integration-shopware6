<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Catalog\Product\Property\CustomFieldBridge;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\CurrencyComposer;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_Catalog_Attribute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;

class ChildProductMapping extends SimpleProductMapping
{
    public function __construct(
        ContextManager $contextManager,
        CustomFieldBridge $customFieldSetBridge,
        SortTree $sortTree,
        ConfigBridge $configBridge,
        TierPriceMapping $tierPriceMapping,
        Formatter $translation,
        CurrencyComposer $currencyComposer,
        AbstractProductCrossSellingRoute $crossSellingRoute
    ) {
        parent::__construct($contextManager, $customFieldSetBridge, $sortTree, $configBridge, $tierPriceMapping,
            $translation, $currencyComposer, $crossSellingRoute);
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
            $itemAttribute->setLabel($option->getTranslation('name') ?: $option->getName());
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
