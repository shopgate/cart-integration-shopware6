<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Catalog\Product\ProductBridge;
use Shopgate\Shopware\Catalog\Product\Property\CustomFieldBridge;
use Shopgate\Shopware\Catalog\Product\Property\PropertyBridge;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\CurrencyComposer;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_AbstractExport;
use Shopgate_Model_Catalog_AttributeGroup;
use Shopgate_Model_Catalog_Product;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ConfigProductMapping extends SimpleProductMapping
{
    public function __construct(
        protected ContextManager $contextManager,
        protected CustomFieldBridge $customFieldSetBridge,
        protected SortTree $sortTree,
        protected PriceMapping $priceMapping,
        protected TierPriceMapping $tierPriceMapping,
        protected Formatter $translation,
        protected CurrencyComposer $currencyComposer,
        protected PropertyBridge $productProperties,
        protected ProductBridge $productBridge,
        protected Shopgate_Model_AbstractExport $childProductMapping,
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
    }

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
                $attributeGroup->setLabel($optionGroup->getTranslation('name') ?: $optionGroup->getName());
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
        $variantId = $this->checkVariantListingConfig() ?? $this->productBridge->findBestVariant($this->item->getId());

        foreach ($children as $child) {
            /** @var ChildProductMapping $exportChild */
            $exportChild = clone $this->childProductMapping;
            $result[] = $exportChild
                ->setItem($child)
                ->setCategoryMap($this->categoryMap)
                ->setDefaultChildId($variantId ?? $children->getIds()[0])
                ->generateData();
        }
        parent::setChildren($result);
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function checkVariantListingConfig(): ?string
    {
        if (($listingConfig = $this->item->getVariantListingConfig()) === null) {
            return null;
        }

        return $listingConfig->getMainVariantId();
    }
}
