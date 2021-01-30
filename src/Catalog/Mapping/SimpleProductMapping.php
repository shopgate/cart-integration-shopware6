<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Exception;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_CategoryPath;
use Shopgate_Model_Catalog_Identifier;
use Shopgate_Model_Catalog_Price;
use Shopgate_Model_Catalog_Product;
use Shopgate_Model_Catalog_Property;
use Shopgate_Model_Catalog_Relation;
use Shopgate_Model_Catalog_Tag;
use Shopgate_Model_Catalog_Visibility;
use Shopgate_Model_Media_Image;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;

class SimpleProductMapping extends Shopgate_Model_Catalog_Product
{
    /** @var ProductEntity */
    protected $item;
    /** @var ContextManager */
    protected $contextManager;
    /** @var int */
    protected $sortOrder;

    /**
     * @param ContextManager $contextManager
     * @param int $sortOrder
     */
    public function __construct(ContextManager $contextManager, int $sortOrder)
    {
        $this->contextManager = $contextManager;
        $this->sortOrder = $sortOrder;
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
            throw new Exception('Could not find context currency: ' . $currencyId);
        }

        $shopgatePrice = $this->getPrice();

        //todo-konstantin this is a hotfix, please check
        $shopgatePrice->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS);

        $shopgatePrice->setPrice($shopwarePrice->getGross());

        $shopgatePrice->setMsrp($shopwarePrice->getListPrice() ? $shopwarePrice->getListPrice()->getGross() : 0);
        if ($this->item->getPurchasePrices() && $cost = $this->item->getPurchasePrices()
                ->getCurrencyPrice($currencyId)) {
            $shopgatePrice->setCost($cost->getGross());
        }

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
     */
    public function setCategoryPaths(): void
    {
        $paths = [];
        foreach ($this->item->getCategories() as $category) {
            $path = new Shopgate_Model_Catalog_CategoryPath();
            $path->setSortOrder($this->sortOrder);
            $path->setUid($category->getId());
            $paths[] = $path;
        }
        parent::setCategoryPaths($paths);
    }

    public function setShipping(): void
    {
        $shipping = $this->getShipping();
        $shipping->setIsFree($this->item->getShippingFree());
        parent::setShipping($shipping);
    }

    public function setVisibility(): void
    {
        $visibility = $this->getVisibility();
        $visible = $this->item->getActive()
            ? Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG_AND_SEARCH
            : Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_NOTHING;
        $visibility->setLevel($visible);
        parent::setVisibility($visibility);
    }

    public function setManufacturer(): void
    {
        $manufacturer = $this->getManufacturer();
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
        $stock = $this->getStock();
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
        //todo-konstantin this is a hotfix, please check
        $identifiers = [];
        if ($this->item->getEan()) {
            $identifier = new Shopgate_Model_Catalog_Identifier();
            $identifier->setType('ean');
            $identifier->setValue($this->item->getEan());
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
