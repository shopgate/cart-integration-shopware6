<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Catalog\Product\Property\CustomFieldBridge;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\CurrencyComposer;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_Catalog_Attribute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ChildProductMapping extends SimpleProductMapping
{
    /**
     * @var array|mixed|\Shopgate_Model_Abstract|string|null
     */
    private ?string $defaultChildId;

    public function __construct(
        ContextManager $contextManager,
        CustomFieldBridge $customFieldSetBridge,
        SortTree $sortTree,
        PriceMapping $priceMapping,
        TierPriceMapping $tierPriceMapping,
        Formatter $translation,
        CurrencyComposer $currencyComposer,
        ExtendedClassFactory $classFactory,
        AbstractProductCrossSellingRoute $crossSellingRoute,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct(
            $contextManager,
            $customFieldSetBridge,
            $sortTree,
            $priceMapping,
            $tierPriceMapping,
            $translation,
            $currencyComposer,
            $classFactory,
            $crossSellingRoute,
            $eventDispatcher
        );
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

    public function setDefaultChildId(string $defaultChildId): ChildProductMapping
    {
        $this->defaultChildId = $defaultChildId;

        return $this;
    }

    public function getDefaultChildId(): ?string
    {
        return $this->defaultChildId;
    }

    public function setIsDefaultChild(): void
    {
        parent::setIsDefaultChild($this->getDefaultChildId() === $this->item->getId());
    }
}
