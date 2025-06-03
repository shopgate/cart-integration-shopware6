<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use ReflectionException;
use Shopgate\Shopware\Catalog\Mapping\Events\AfterSimpleProductPropertyMapEvent;
use Shopgate\Shopware\Catalog\Product\ProductExportExtension;
use Shopgate\Shopware\Catalog\Product\Property\CustomFieldBridge;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductCollection;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\CurrencyComposer;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_Catalog_CategoryPath;
use Shopgate_Model_Catalog_Identifier;
use Shopgate_Model_Catalog_Manufacturer;
use Shopgate_Model_Catalog_Price;
use Shopgate_Model_Catalog_Product;
use Shopgate_Model_Catalog_Relation;
use Shopgate_Model_Catalog_Shipping;
use Shopgate_Model_Catalog_Stock;
use Shopgate_Model_Catalog_Tag;
use Shopgate_Model_Catalog_Visibility;
use Shopgate_Model_Media_Image;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SimpleProductMapping extends Shopgate_Model_Catalog_Product
{
    use MapTrait;

    /** @var SalesChannelProductEntity */
    protected $item;
    protected CategoryProductCollection $categoryMap;

    public function __construct(
        protected ContextManager $contextManager,
        protected CustomFieldBridge $customFieldSetBridge,
        protected SortTree $sortTree,
        protected PriceMapping $priceMapping,
        protected TierPriceMapping $tierPriceMapping,
        protected Formatter $formatter,
        protected CurrencyComposer $currencyComposer,
        protected ExtendedClassFactory $classFactory,
        protected AbstractProductCrossSellingRoute $crossSellingRoute,
        protected EventDispatcherInterface $eventDispatcher,
        protected SystemConfigService $systemConfigService
    ) {
        parent::__construct();
    }

    public function setUid(): void
    {
        parent::setUid($this->item->getId());
    }

    public function setName(): void
    {
        parent::setName($this->item->getTranslation('name') ?: $this->item->getName());
    }

    public function setTaxClass(): void
    {
        if ($this->item->getTaxId()) {
            parent::setTaxClass('tax_' . $this->item->getTaxId());
        }
    }

    public function setCurrency(): void
    {
        if ($currency = $this->contextManager->getSalesContext()->getSalesChannel()->getCurrency()) {
            parent::setCurrency($currency->getIsoCode());
        }
    }

    public function setDescription(): void
    {
        parent::setDescription($this->item->getTranslation('description') ?: $this->item->getDescription());
    }

    public function setDeeplink(): void
    {
        parent::setDeeplink($this->getDeepLinkUrl($this->item));
    }

    private function getDeepLinkUrl(ProductEntity $productEntity): string
    {
        $channel = $this->contextManager->getSalesContext();
        $entityList = $productEntity->getSeoUrls()?->filterBySalesChannelId($channel->getSalesChannelId());

        return $entityList ? $this->getSeoUrl($channel, $entityList->first()) : '';
    }

    public function setWeight(): void
    {
        parent::setWeight($this->item->getWeight());
    }

    /**
     * @throws ReflectionException
     */
    public function setPrice(): void
    {
        $shopgatePrice = new Shopgate_Model_Catalog_Price();
        $shopgatePrice->setType($this->priceMapping->getPriceType());
        if ($shopwarePrice = $this->currencyComposer->extractCalculatedPrice($this->item->getPrice())) {
            $shopgatePrice->setPrice($this->priceMapping->mapPrice($shopwarePrice));
            if ($listPrice = $shopwarePrice->getListPrice()) {
                $shopgatePrice->setMsrp(
                    $this->priceMapping->mapPrice($this->currencyComposer->toCalculatedPrice($listPrice))
                );
            }

            if ($priceCollection = $this->item->getPrices()) {
                $priceCollection->sortByQuantity();
                $shopgatePrice->setPrice($this->priceMapping->mapPrice($shopwarePrice));
                $shopgatePrice->setTierPricesGroup(
                    $this->tierPriceMapping->mapTierPrices($priceCollection, $shopwarePrice)
                );
            }
        }

        if ($this->item->getPurchasePrices()
            && $cost = $this->currencyComposer->extractCalculatedPrice($this->item->getPurchasePrices())) {
            $shopgatePrice->setCost($this->priceMapping->mapPrice($cost));
        }

        /**
         * Base Price
         */
        $basePrice = null;
        if ($unitValue = $this->item->getPurchaseUnit()) {
            $label = $this->formatter->translate('listing.boxUnitLabel', []);
            $unitName = $this->item->getUnit() ? $this->item->getUnit()->getTranslation('name') : '';
            $basePrice .= "$label $unitValue $unitName "; // Content: 50 ml
        }
        // if 'basic unit' is not missing
        if ($refPrice = $this->item->getCalculatedPrice()->getReferencePrice()) {
            $formatted = $this->currencyComposer->formatCurrency($refPrice->getPrice());
            $basePrice .= "($formatted / {$refPrice->getReferenceUnit()} {$refPrice->getUnitName()})"; // ($10 / 100 ml)
        }
        $shopgatePrice->setBasePrice($basePrice);
        parent::setPrice($shopgatePrice);
    }

    public function setImages(): void
    {
        $swMedia = $this->item->getMedia() ?: [];
        $images = [];
        foreach ($swMedia as $productMedia) {
            if (!$media = $productMedia->getMedia()) {
                continue;
            }
            $image = new Shopgate_Model_Media_Image();
            $image->setUid($media->getId());
            $image->setAlt($media->getTranslation('alt') ?: $media->getAlt());
            $image->setTitle($media->getTranslation('title') ?: $media->getTitle());
            $image->setUrl($media->getUrl());
            $image->setSortOrder($productMedia->getPosition());
            $image->setIsCover(
                (int)$this->item->getCoverId() && $this->item->getCoverId() === $productMedia->getId()
            );
            $images[] = $image;
        }
        parent::setImages($images);
    }

    /**
     * Setting the same sort order for every category as supposedly
     * we have a sort order for the whole catalog.
     */
    public function setCategoryPaths(): void
    {
        $categoryMap = $this->categoryMap->filterByProperty('productId', $this->item->getId());

        $paths = [];
        foreach ($categoryMap as $mapping) {
            $path = new Shopgate_Model_Catalog_CategoryPath();
            $path->setUid($mapping->getCategoryId());
            $path->setSortOrder($mapping->getSortOrder());
            $paths[] = $path;
        }
        parent::setCategoryPaths($paths);
    }

    public function setShipping(): void
    {
        $shipping = new Shopgate_Model_Catalog_Shipping();
        $shipping->setIsFree($this->item->getShippingFree());
        parent::setShipping($shipping);
    }

    public function setVisibility(): void
    {
        $visibility = new Shopgate_Model_Catalog_Visibility();
        $visible = $this->item->getActive()
            ? Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG_AND_SEARCH
            : Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_NOTHING;
        $visibility->setLevel($visible);
        parent::setVisibility($visibility);
    }

    public function setManufacturer(): void
    {
        $manufacturer = new Shopgate_Model_Catalog_Manufacturer();
        if ($swEntity = $this->item->getManufacturer()) {
            $manufacturer->setTitle($swEntity->getTranslation('name') ?: $swEntity->getName());
        }
        $manufacturer->setUid($this->item->getManufacturerId());
        $manufacturer->setItemNumber($this->item->getManufacturerNumber());
        parent::setManufacturer($manufacturer);
    }

    public function setProperties(): void
    {
        $properties = [];
        if ($shopwareProps = $this->item->getProperties()) {
            // different properties per group, e.g. 3 'width' props with different values, 5mm, 10mm, 12mm
            foreach ($shopwareProps as $shopwareProp) {
                $uid = $shopwareProp->getGroup() ? $shopwareProp->getGroup()->getId() : $shopwareProp->getId();
                $value = $shopwareProp->getTranslation('name') ?: $shopwareProp->getName();
                if (isset($properties[$uid])) {
                    $value = $properties[$uid]->getValue() . ', ' . $value;
                }
                $property = $this->classFactory->createProperty()->setUid($uid)->setValue($value);
                if ($group = $shopwareProp->getGroup()) {
                    $property->setLabel($group->getTranslation('name') ?: $group->getName());
                } else {
                    $property->setLabel($uid);
                }
                $properties[$uid] = $property;
            }
        }

        $fields = [
            ...$this->item->getCustomFields() ?? [],
            ...($this->item->getManufacturer()?->getCustomFields() ?? []),
        ];
        if ($fields) {
            $locale = $this->formatter->getLocaleCode() ?: 'en-GB';
            $allFields = $this->customFieldSetBridge->getAllProductFieldSets();
            foreach ($fields as $key => $value) {
                $entity = $allFields->filterByProperty('name', $key)->first();
                if (!$entity) {
                    continue;
                }
                // Use language label, fallback "my_key" -> "My Key"
                $labelRetriever = function (array $config, string $fallback) use ($locale) {
                    return $config['label'][$locale]
                        ?? $config['label']['en-GB']
                        ?? $fallback;
                };
                $label = $labelRetriever($entity->getConfig(), (string)$key);
                // select & multi-select handling
                if (isset($entity->getConfig()['options'])) {
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    $valueMap = array_map(function (string $techName) use ($entity, $labelRetriever) {
                        $found = array_filter(
                            $entity->getConfig()['options'],
                            fn(array $option) => $option['value'] === $techName
                        );
                        $config = current($found);
                        if ($config) {
                            return $labelRetriever($config, $techName);
                        }
                        return $techName;
                    }, $value);
                    $value = implode(', ', $valueMap);
                }
                $properties[] = $this->classFactory->createProperty()
                    ->setUid($entity->getId())
                    ->setValue($value)
                    ->setLabel($label);
            }
        }

        /**
         * Any other fields that are not part of the Properties or Custom lists.
         * Please note that it uses very specific translation key domain, and
         * a fallback when trans. is not available
         */
        $additionalFields = ['width', 'height', 'length', 'packUnit', 'packUnitPlural'];
        foreach ($additionalFields as $field) {
            $value = $this->item->get($field);
            if (empty($value)) {
                continue;
            }
            $label = $this->formatter->translate('component.product.feature.label.' . $field, []);
            $properties[$field] = $this->classFactory->createProperty()
                ->setUid($field)
                ->setLabel($label ? rtrim($label, ':') : $this->formatter->camelCaseToSpaced($field))
                ->setValue($value);
        }

        if ($this->item->getCheapestPrice()
            && ($price = $this->currencyComposer->extractCalculatedPrice($this->item->getCheapestPrice()->getPrice()))
            && $this->item->getCalculatedCheapestPrice()->getUnitPrice() !== $this->item->getCalculatedPrice()
                ->getUnitPrice()
        ) {
            $properties[] = $this->classFactory->createProperty()
                ->setUid('cheapestPrice')
                ->setAndTranslateLabel('sg-catalog.cheapestPriceLabel', [], null)
                ->setValue($price);
        }

        $calculated = $this->currencyComposer->extractCalculatedPrice($this->item->getPrice());
        if ($calculated && $regPrice = $calculated->getRegulationPrice()
        ) {
            $properties[] = $this->classFactory->createProperty()
                ->setUid('previousPrice')
                ->setAndTranslateLabel('sg-catalog.previousPriceLabel', [], null)
                ->setValue($regPrice);
        }

        $eventProperties = $this->eventDispatcher->dispatch(
            new AfterSimpleProductPropertyMapEvent($properties, $this->item)
        )->getProperties();
        parent::setProperties($eventProperties);
    }

    public function setStock(): void
    {
        $stock = new Shopgate_Model_Catalog_Stock();
        $availableStock = $this->item->getAvailableStock();
        $allowBackorders = !$this->item->getIsCloseout();
        $stock->setUseStock(!$allowBackorders);
        $stock->setIsSaleable($availableStock > 0 || $allowBackorders);
        $stock->setStockQuantity($availableStock);
        $stock->setMinimumOrderQuantity($this->item->getMinPurchase());
        $stock->setMaximumOrderQuantity($this->item->getMaxPurchase());
        $stock->setBackorders($allowBackorders);
        // availability text
        $text = null;
        $deliveryTime = $this->item->getDeliveryTime();
        if ($deliveryTime && $availableStock >= $this->item->getMinPurchase()) {
            //e.g. Available, delivery time 2-5 days
            $text = $this->formatter->translate('detail.deliveryTimeAvailable',
                ['%name%' => $deliveryTime->getTranslation('name')]);
        } elseif ($deliveryTime
            && ($restockTime = $this->item->getRestockTime())
            && $availableStock < $this->item->getMinPurchase()) {
            // e.g. Available in 3 days, delivery time 2-5 days
            $text = $this->formatter->translate('detail.deliveryTimeRestock', [
                '%count%' => $restockTime,
                '%restockTime%' => $restockTime,
                '%name%' => $deliveryTime->getTranslation('name')
            ]);
        }
        $stock->setAvailabilityText($text);
        parent::setStock($stock);
    }

    public function setIdentifiers(): void
    {
        $identifiers = [];
        if ($this->item->getEan()) {
            $identifier = new Shopgate_Model_Catalog_Identifier();
            $identifier->setType('ean');
            $identifier->setValue($this->item->getEan());
            $identifiers[] = $identifier;
        }
        if ($this->item->getProductNumber()) {
            $identifier = new Shopgate_Model_Catalog_Identifier();
            $identifier->setType('sku');
            $identifier->setValue($this->item->getProductNumber());
            $identifiers[] = $identifier;
        }
        parent::setIdentifiers($identifiers);
    }

    public function setTags(): void
    {
        $shopwareTags = $this->item->getTags() ?: [];
        $tags = [];
        foreach ($shopwareTags as $shopwareTag) {
            $tag = new Shopgate_Model_Catalog_Tag();
            $tag->setUid($shopwareTag->getId());
            $tag->setValue($shopwareTag->getTranslation('name') ?: $shopwareTag->getName());
            $tags[] = $tag;
        }
        parent::setTags($tags);
    }

    public function setInternalOrderInfo(): void
    {
        if ($extension = $this->item->getExtension(ProductExportExtension::EXT_KEY)) {
            parent::setInternalOrderInfo((string)$extension);
        }
    }

    /**
     * We export only 4 sliders per SG limitations
     */
    public function setRelations(): void
    {
        if (!$this->item->getCrossSellings()) {
            parent::setRelations([]);
            return;
        }
        $channelId = $this->contextManager->getSalesContext()->getSalesChannelId();
        $exportAs = $this->systemConfigService->getString(ConfigBridge::PRODUCT_CROSS_SELL_EXPORT, $channelId);
        if ($exportAs !== ConfigBridge::CROSS_SELL_EXPORT_TYPE_REL && $exportAs !== '') {
            parent::setRelations([]);
            return;
        }

        $criteria = new Criteria();
        $criteria->setTitle('shopgate::cross-selling::product-id');
        $crossSellings = $this->crossSellingRoute->load(
            $this->item->getId(),
            new Request(),
            $this->contextManager->getSalesContext(),
            $criteria
        )->getResult();
        $typeList = [
            Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_CROSSSELL,
            Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL,
            Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_CUSTOM,
            Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_RELATION,
        ];
        $export = [];
        foreach ($crossSellings as $element) {
            if (count($typeList) === 0 || $element->getProducts()->count() === 0) {
                continue;
            }
            $relation = new Shopgate_Model_Catalog_Relation();
            $relation->setType(array_shift($typeList));
            $crossSellingEntity = $element->getCrossSelling();
            $relation->setLabel($crossSellingEntity->getTranslation('name'));
            $itemIds = [];
            foreach ($element->getProducts() as $product) {
                $id = $product->getParentId() ?: $product->getId();
                $itemIds[$id] = $id;
            }
            $relation->setValues($itemIds);
            $export[] = $relation;
        }
        parent::setRelations($export);
    }

    public function getItem(): SalesChannelProductEntity
    {
        return $this->item;
    }

    /**
     * Rewritten to avoid a php8 warning printed in XML
     */
    public function setData($key, $value = null): self
    {
        if (null === $value && in_array($key, ['children', 'attribute_groups', 'inputs', 'attributes'])) {
            $value = [];
        }
        return parent::setData($key, $value);
    }

    public function setCategoryMap(CategoryProductCollection $collection): self
    {
        $this->categoryMap = $collection;

        return $this;
    }
}
