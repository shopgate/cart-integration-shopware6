<?php

namespace Shopgate\Shopware\Export\Catalog\Mapping;

use Exception;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_CategoryPath;
use Shopgate_Model_Catalog_Identifier;
use Shopgate_Model_Catalog_Product;
use Shopgate_Model_Catalog_Property;
use Shopgate_Model_Catalog_Tag;
use Shopgate_Model_Catalog_Visibility;
use Shopgate_Model_Media_Image;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;

class ProductMapping extends Shopgate_Model_Catalog_Product
{
    /** @var ProductEntity */
    protected $item;
    /** @var ContextManager */
    private $contextManager;
    /** @var int */
    private $sortOrder;

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
        parent::setTaxClass($this->item->getTaxId());
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
        $shopgatePrice->setPrice($shopwarePrice->getGross());
        $shopgatePrice->setMsrp($shopwarePrice->getListPrice() ? $shopwarePrice->getListPrice()->getGross() : 0);
        if ($this->item->getPurchasePrices() && $cost = $this->item->getPurchasePrices()
                ->getCurrencyPrice($currencyId)) {
            $shopgatePrice->setCost($cost->getGross());
        }

        //todo: cannot easily support tier prices as they are customizable
        /*if ($listPrices = $this->item->getListingPrices()) {
            $priceGroups = [];
            foreach ($listPrices as $price) {
                $priceGroup = new \Shopgate_Model_Catalog_TierPrice();
                $priceGroups[] = $priceGroup;
            }
            $shopgatePrice->setTierPricesGroup($priceGroups);
        }*/
        parent::setPrice($shopgatePrice);
    }

    public function setImages(): void
    {
        if (!$this->item->getMedia()) {
            return;
        }
        $images = [];
        foreach ($this->item->getMedia()->getMedia() as $key => $media) {
            $image = new Shopgate_Model_Media_Image();
            $image->setUid($media->getId());
            $image->setAlt($media->getAlt());
            $image->setTitle($media->getTitle());
            $image->setUrl($media->getUrl());
            $image->setSortOrder($key);
            $image->setIsCover($this->item->getCoverId() && $this->item->getCoverId() === $media->getId());
            $images[] = $image;
            //todo: finish up, sort order test
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
        if (!$this->item->getManufacturer()) {
            return;
        }
        $manufacturer = $this->getManufacturer();
        $manufacturer->setItemNumber($this->item->getManufacturer()->getId());
        $manufacturer->setTitle($this->item->getManufacturer()->getName());
        parent::setManufacturer($manufacturer);
    }

    public function setProperties(): void
    {
        if (!$shopwareProps = $this->item->getProperties()) {
            return;
        }
        $properties = [];
        // different properties per group, e.g. 3 width props with different values, 5mm, 10mm, 12mm
//        foreach ($shopwareProps as $shopwareProp) {
//            $property = new \Shopgate_Model_Catalog_Property();
//            $property->setUid($shopwareProp->getId());
//            $property->setValue($shopwareProp->getName());
//            if ($shopwareProp->getGroup()) {
//                $property->setLabel($shopwareProp->getGroup()->getName());
//            }
//            $properties[] = $property;
//        }

        // manually merged values, one 'width' property with value '10mm, 5mm, 12mm'
        foreach ($shopwareProps->getGroups() as $group) {
            $props = $shopwareProps->filterByGroupId($group->getId());
            if (!$props) {
                //todo-log
                continue;
            }
            $property = new Shopgate_Model_Catalog_Property();
            $property->setUid($group->getId());
            $property->setLabel($group->getName());
            $value = [];
            foreach ($props as $shopwareProp) {
                $value[] = $shopwareProp->getName();
            }
            $property->setValue(implode(', ', $value));
            $properties[] = $property;

        }

        parent::setProperties($properties);
    }

    public function setStock(): void
    {
        $stock = $this->getStock();
        $stock->setUseStock($this->item->getAvailableStock() > 0);
        $stock->setIsSaleable($this->item->getAvailableStock() > 0);
        $stock->setMinimumOrderQuantity($this->item->getMinPurchase());
        $stock->setMaximumOrderQuantity($this->item->getMaxPurchase());
        //$stock->setBackorders(!$this->item->getIsCloseout());
        parent::setStock($stock);
    }

    public function setIdentifiers(): void
    {
        $identifier = new Shopgate_Model_Catalog_Identifier();
        if ($this->item->getEan()) {
            $identifier->setType('ean');
            $identifier->setValue($this->item->getEan());
        }
        parent::setIdentifiers([$identifier]);
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
}
