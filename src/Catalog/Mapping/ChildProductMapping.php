<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Catalog\Product\Property\CustomFieldBridge;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\CurrencyComposer;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_Abstract;
use Shopgate_Model_Catalog_Attribute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ChildProductMapping extends SimpleProductMapping
{
    /**
     * @var array|mixed|Shopgate_Model_Abstract|string|null
     */
    private ?string $defaultChildId;

    public function __construct(
        protected ContextManager $contextManager,
        protected CustomFieldBridge $customFieldSetBridge,
        protected SortTree $sortTree,
        protected PriceMapping $priceMapping,
        protected TierPriceMapping $tierPriceMapping,
        protected Formatter $translation,
        protected CurrencyComposer $currencyComposer,
        protected ExtendedClassFactory $classFactory,
        protected AbstractProductCrossSellingRoute $crossSellingRoute,
        protected EventDispatcherInterface $eventDispatcher,
        protected SystemConfigService $systemConfigService
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
            $eventDispatcher,
            $systemConfigService
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
