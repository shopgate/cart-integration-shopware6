<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Shopgate\Shopware\Catalog\Product\ProductExportExtension;
use Shopgate\Shopware\Catalog\Product\Property\CustomFieldBridge;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_Catalog_CategoryPath;
use Shopgate_Model_Catalog_Identifier;
use Shopgate_Model_Catalog_Manufacturer;
use Shopgate_Model_Catalog_Price;
use Shopgate_Model_Catalog_Product;
use Shopgate_Model_Catalog_Property;
use Shopgate_Model_Catalog_Relation;
use Shopgate_Model_Catalog_Shipping;
use Shopgate_Model_Catalog_Stock;
use Shopgate_Model_Catalog_Tag;
use Shopgate_Model_Catalog_Visibility;
use Shopgate_Model_Media_Image;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;

class SimpleProductMapping extends Shopgate_Model_Catalog_Product
{
    use MapTrait;

    /** @var SalesChannelProductEntity */
    protected $item;
    protected ContextManager $contextManager;
    protected SortTree $sortTree;
    protected TierPriceMapping $tierPriceMapping;
    protected Formatter $formatter;
    private CustomFieldBridge $customFieldSetBridge;

    public function __construct(
        ContextManager $contextManager,
        CustomFieldBridge $customFieldSetBridge,
        SortTree $sortTree,
        TierPriceMapping $tierPriceMapping,
        Formatter $formatter
    ) {
        $this->contextManager = $contextManager;
        $this->customFieldSetBridge = $customFieldSetBridge;
        $this->sortTree = $sortTree;
        $this->tierPriceMapping = $tierPriceMapping;
        $this->formatter = $formatter;
        parent::__construct();
    }

    public function setUid(): void
    {
        parent::setUid($this->item->getId());
    }

    public function setName(): void
    {
        parent::setName($this->item->getName());
    }

    public function setTaxClass(): void
    {
        if ($this->item->getTaxId()) {
            parent::setTaxClass('tax_' . $this->item->getTaxId());
        }
    }

    /**
     * @throws MissingContextException
     */
    public function setCurrency(): void
    {
        if ($currency = $this->contextManager->getSalesContext()->getSalesChannel()->getCurrency()) {
            parent::setCurrency($currency->getIsoCode());
        }
    }

    public function setDescription(): void
    {
        parent::setDescription($this->item->getDescription());
    }

    /**
     * @throws MissingContextException
     */
    public function setDeeplink(): void
    {
        parent::setDeeplink($this->getDeepLinkUrl($this->item));
    }

    /**
     * @throws MissingContextException
     */
    private function getDeepLinkUrl(ProductEntity $productEntity): string
    {
        $channel = $this->contextManager->getSalesContext()->getSalesChannel();
        $entityList = $productEntity->getSeoUrls()
            ? $productEntity->getSeoUrls()->filterBySalesChannelId($channel->getId())
            : null;

        return $entityList ? $this->getSeoUrl($channel, $entityList->first()) : '';
    }

    public function setWeight(): void
    {
        parent::setWeight($this->item->getWeight());
    }

    /**
     * @throws MissingContextException
     * @throws Exception
     */
    public function setPrice(): void
    {
        $currencyId = $this->contextManager->getSalesContext()->getCurrency()->getId();
        if (!$shopwarePrice = $this->item->getCurrencyPrice($currencyId)) {
            throw new MissingContextException('Could not find price for currency: ' . $currencyId);
        }
        $highestPrice = $this->tierPriceMapping->getHighestPrice($this->item->getPrices(), $shopwarePrice);
        $shopgatePrice = new Shopgate_Model_Catalog_Price();
        $shopgatePrice->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS);
        $shopgatePrice->setPrice($highestPrice->getGross());
        $shopgatePrice->setMsrp($shopwarePrice->getListPrice() ? $shopwarePrice->getListPrice()->getGross() : 0);
        if ($this->item->getPurchasePrices() && $cost = $this->item->getPurchasePrices()
                ->getCurrencyPrice($currencyId)) {
            $shopgatePrice->setCost($cost->getGross());
        }
        $shopgatePrice->setTierPricesGroup(
            $this->tierPriceMapping->mapTierPrices($this->item->getPrices(), $highestPrice)
        );

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
            $formatted = $this->formatter->formatCurrency($refPrice->getPrice());
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
            $image->setAlt($media->getAlt());
            $image->setTitle($media->getTitle());
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
     * @throws MissingContextException
     * @throws InvalidArgumentException
     */
    public function setCategoryPaths(): void
    {
        $sortTree = $this->sortTree->getSortTree();
        $paths = [];
        foreach ($this->item->getCategories() as $category) {
            if ($this->isRootCategory($category->getId())) {
                continue;
            }
            $path = new Shopgate_Model_Catalog_CategoryPath();
            $path->setUid($category->getId());
            if (isset($sortTree[$category->getId()][$this->item->getId()])) {
                $path->setSortOrder($sortTree[$category->getId()][$this->item->getId()]);
            }
            $paths[] = $path;
        }
        parent::setCategoryPaths($paths);
    }

    /**
     * Check if provided category is a root category
     *
     * @param string $id
     * @return bool
     * @throws MissingContextException
     */
    private function isRootCategory(string $id): bool
    {
        return $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId() === $id;
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
        if ($this->item->getManufacturer()) {
            $manufacturer->setTitle($this->item->getManufacturer()->getName());
        }
        $manufacturer->setUid($this->item->getManufacturerId());
        $manufacturer->setItemNumber($this->item->getManufacturerNumber());
        parent::setManufacturer($manufacturer);
    }

    /**
     * @throws MissingContextException
     */
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
                $property = new Shopgate_Model_Catalog_Property();
                $property->setUid($uid);
                $property->setValue($this->translateEntityValue($value));
                $label = $shopwareProp->getGroup()
                    ? $shopwareProp->getGroup()->getTranslation('name')
                    : $shopwareProp->getGroup()->getName();
                $property->setLabel($label ?: $uid);
                $properties[$uid] = $property;
            }
        }

        if ($fields = $this->item->getCustomFields()) {
            $locale = $this->formatter->getLocaleCode() ?: 'en-GB';
            $allFields = $this->customFieldSetBridge->getAllProductFieldSets();
            foreach ($fields as $key => $value) {
                $entity = $allFields->filterByProperty('name', $key)->first();
                if (!$entity) {
                    continue;
                }
                $customField = new Shopgate_Model_Catalog_Property();
                $customField->setUid($entity->getId());
                $customField->setValue($this->translateEntityValue($value));
                // Use language label, fallback "my_key" -> "My Key"
                $label = $entity->getConfig()['label'][$locale]
                    ?? $entity->getConfig()['label']['en-GB']
                    ?? implode(' ', array_map(static function ($item) {
                        return ucfirst($item);
                    }, explode('_', $key)));
                $customField->setLabel($label);
                $properties[] = $customField;
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
            $property = new Shopgate_Model_Catalog_Property();
            $property->setUid($field);
            $property->setLabel($label ? rtrim($label, ':') : $this->formatter->camelCaseToSpaced($field));
            $property->setValue($value);
            $properties[$field] = $property;
        }

        /**
         * Supposed to get the cheapest price, cannot confirm or test this SW 6.4.10 feature
         */
        if (method_exists($this->item, 'getCalculatedCheapestPrice')
            && ($cheapest = $this->item->getCalculatedCheapestPrice())
            && $cheapest->getUnitPrice() !== $this->item->getCalculatedPrice()->getUnitPrice()
        ) {
            $property = new Shopgate_Model_Catalog_Property();
            $property->setUid('cheapestPrice');
            $property->setLabel('Günstigster Preis');
            $property->setValue((string)$cheapest->getUnitPrice());
            $properties[] = $property;
        }

        // SW 6.4.10+
        $calculated = $this->item->getCalculatedPrice();
        if (method_exists($calculated, 'getRegulationPrice') && $regPrice = $calculated->getRegulationPrice()) {
            $property = new Shopgate_Model_Catalog_Property();
            $property->setUid('previousPrice');
            $property->setLabel('Vorheriger Preis');
            $property->setValue((string)$regPrice->getPrice());
            $properties[] = $property;
        }

        parent::setProperties($properties);
    }

    /**
     * Translates FALSE value field to string
     *
     * @param mixed $value
     * @return string
     */
    private function translateEntityValue($value): string
    {
        return $value === false ? '0' : (string)$value;
    }

    /**
     * @throws MissingContextException
     */
    public function setStock(): void
    {
        $stock = new Shopgate_Model_Catalog_Stock();
        $allowBackorders = !$this->item->getIsCloseout();
        $stock->setUseStock(true);
        $stock->setIsSaleable($this->item->getAvailableStock() > 0 || $allowBackorders);
        $stock->setStockQuantity($this->item->getAvailableStock());
        $stock->setMinimumOrderQuantity($this->item->getMinPurchase());
        $stock->setMaximumOrderQuantity($this->item->getMaxPurchase());
        $stock->setBackorders($allowBackorders);
        // availability text
        $text = null;
        $deliveryTime = $this->item->getDeliveryTime();
        if ($deliveryTime && $this->item->getAvailableStock() >= $this->item->getMinPurchase()) {
            //e.g. Available, delivery time 2-5 days
            $text = $this->formatter->translate('detail.deliveryTimeAvailable',
                ['%name%' => $deliveryTime->getTranslation('name')]);
        } elseif ($deliveryTime
            && ($restockTime = $this->item->getRestockTime())
            && $this->item->getAvailableStock() < $this->item->getMinPurchase()) {
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
            $tag->setValue($shopwareTag->getName());
            $tags[] = $tag;
        }
        parent::setTags($tags);
    }

    public function setRelations(): void
    {
        $relationProducts = [];
        if ($crossSellProducts = $this->item->getCrossSellings()) {
            $relationProduct = new Shopgate_Model_Catalog_Relation();
            $relationProduct->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_CROSSSELL);
            $values = [];
            foreach ($crossSellProducts as $crossSellProduct) {
                $values[] = $crossSellProduct->getId();
            }
            $relationProduct->setValues($values);
            $relationProducts[] = $relationProduct;
        }
        parent::setRelations($relationProducts);
    }

    public function setInternalOrderInfo(): void
    {
        if ($extension = $this->item->getExtension(ProductExportExtension::EXT_KEY)) {
            parent::setInternalOrderInfo((string)$extension);
        }
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
}
