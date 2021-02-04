<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
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
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;

class SimpleProductMapping extends Shopgate_Model_Catalog_Product
{
    /** @var ProductEntity */
    protected $item;
    /** @var ContextManager */
    protected $contextManager;
    /** @var SortTree */
    protected $sortTree;
    /** @var TierPriceMapping */
    protected $tierPriceMapping;

    /**
     * @param ContextManager $contextManager
     * @param SortTree $sortTree
     * @param TierPriceMapping $tierPriceMapping
     */
    public function __construct(ContextManager $contextManager, SortTree $sortTree, TierPriceMapping $tierPriceMapping)
    {
        $this->contextManager = $contextManager;
        $this->sortTree = $sortTree;
        $this->tierPriceMapping = $tierPriceMapping;
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
        parent::setTaxClass('tax_' . $this->item->getTaxId());
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
     * @param ProductEntity $productEntity
     * @return string
     * @throws MissingContextException
     */
    private function getDeepLinkUrl(ProductEntity $productEntity): string
    {
        $channel = $this->contextManager->getSalesContext()->getSalesChannel();
        $entityList = $productEntity->getSeoUrls()
            ? $productEntity->getSeoUrls()->filterBySalesChannelId($channel->getId())
            : null;
        if ($entityList && $entity = $entityList->first()) {
            // intentional use of get, URL can be null which throws Shopware exception
            if ($entity->get('url')) {
                return $entity->getUrl();
            }
            if (null !== $channel->getDomains()) {
                $domainCollection = $channel->getDomains()->filterByProperty('salesChannelId', $channel->getId());
                /** @var null|SalesChannelDomainEntity $domain */
                $domain = $domainCollection->first();
                return $domain ? $domain->getUrl() . $entity->getPathInfo() : '';
            }
        }

        return '';
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

        parent::setPrice($shopgatePrice);
    }

    public function setImages(): void
    {
        if (!$this->item->getMedia()) {
            return;
        }
        $images = [];
        foreach ($this->item->getMedia() as $productMedia) {
            if (!$media = $productMedia->getMedia()) {
                continue;
            }
            $image = new Shopgate_Model_Media_Image();
            $image->setUid($media->getId());
            $image->setAlt($media->getAlt());
            $image->setTitle($media->getTitle());
            $image->setUrl($media->getUrl());
            $image->setSortOrder(100 - $productMedia->getPosition());
            $image->setIsCover(
                $this->item->getCoverId() && $this->item->getCoverId() === $productMedia->getId()
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
        /** @var CategoryEntity $category */
        foreach ($this->item->getCategories() as $category) {
            $path = new Shopgate_Model_Catalog_CategoryPath();
            $path->setUid($category->getId());
            if (isset($sortTree[$category->getId()][$this->item->getId()])) {
                $path->setSortOrder($sortTree[$category->getId()][$this->item->getId()]);
            }
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
        if ($this->item->getManufacturer()) {
            $manufacturer->setTitle($this->item->getManufacturer()->getName());
        }
        $manufacturer->setUid($this->item->getManufacturerId());
        $manufacturer->setItemNumber($this->item->getManufacturerNumber());
        parent::setManufacturer($manufacturer);
    }

    public function setProperties(): void
    {
        if (!$shopwareProps = $this->item->getProperties()) {
            return;
        }
        $properties = [];
        // different properties per group, e.g. 3 'width' props with different values, 5mm, 10mm, 12mm
        foreach ($shopwareProps as $shopwareProp) {
            $property = new Shopgate_Model_Catalog_Property();
            $property->setUid($shopwareProp->getId());
            $property->setValue($shopwareProp->getName());
            if ($shopwareProp->getGroup()) {
                $property->setLabel($shopwareProp->getGroup()->getName());
            }
            $properties[] = $property;
        }

        parent::setProperties($properties);
    }

    public function setStock(): void
    {
        $stock = new Shopgate_Model_Catalog_Stock();
        $stock->setUseStock($this->item->getAvailableStock() > 0);
        $stock->setIsSaleable($this->item->getAvailableStock() > 0);
        $stock->setStockQuantity($this->item->getAvailableStock());
        $stock->setMinimumOrderQuantity($this->item->getMinPurchase());
        $stock->setMaximumOrderQuantity($this->item->getMaxPurchase());
        //$stock->setBackorders(!$this->item->getIsCloseout());
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
        if (!$shopwareTags = $this->item->getTags()) {
            return;
        }
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
}
