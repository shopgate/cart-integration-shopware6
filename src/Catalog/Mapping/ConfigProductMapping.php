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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ConfigProductMapping extends SimpleProductMapping
{
    protected ContextManager $contextManager;
    protected SortTree $sortTree;
    private PropertyBridge $productProperties;
    private Shopgate_Model_AbstractExport|ChildProductMapping $childProductMapping;
    private ProductBridge $productBridge;

    public function __construct(
        ContextManager $contextManager,
        CustomFieldBridge $customFieldSetBridge,
        SortTree $sortTree,
        PriceMapping $priceMapping,
        TierPriceMapping $tierPriceMapping,
        Formatter $translation,
        CurrencyComposer $currencyComposer,
        PropertyBridge $productProperties,
        ProductBridge $productBridge,
        Shopgate_Model_AbstractExport $childProductMapping,
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
        $this->productProperties = $productProperties;
        $this->childProductMapping = $childProductMapping;
        $this->productBridge = $productBridge;
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
            $exportChild = clone $this->childProductMapping;
            $result[] = $exportChild
                ->setItem($child)
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
