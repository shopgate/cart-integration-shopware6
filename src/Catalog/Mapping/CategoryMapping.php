<?php

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Media_Image;
use Shopware\Core\Content\Category\CategoryEntity;

class CategoryMapping extends Shopgate_Model_Catalog_Category
{
    use MapTrait;

    /** @var CategoryEntity */
    protected $item;
    /** @var null */
    private $parentId;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param ContextManager $contextManager
     */
    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
        parent::__construct();
    }

    /**
     * Generate data dom object by firing a method array
     *
     * @return $this
     */
    public function generateData(): CategoryMapping
    {
        foreach ($this->fireMethods as $method) {
            $this->{$method}($this->item);
        }

        return $this;
    }

    /**
     * Set category sort order
     */
    public function setSortOrder(): void
    {
        parent::setSortOrder($this->item->getCustomFields()['sortOrder'] ?? 0);
    }

    /**
     * Set category id
     */
    public function setUid(): void
    {
        parent::setUid($this->item->getId());
    }

    /**
     * Set category name
     */
    public function setName(): void
    {
        parent::setName($this->item->getName());
    }

    /**
     * Set parent category id
     */
    public function setParentUid(): void
    {
        $rootId = $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId();
        parent::setParentUid($rootId !== $this->item->getParentId() ? $this->item->getParentId() : null);
    }

    /**
     * Category link in shop
     * @throws MissingContextException
     */
    public function setDeeplink(): void
    {
        parent::setDeeplink($this->getDeepLinkUrl($this->item));
    }

    /**
     * @param CategoryEntity $category
     * @return string
     * @throws MissingContextException
     */
    private function getDeepLinkUrl(CategoryEntity $category): string
    {
        $channel = $this->contextManager->getSalesContext()->getSalesChannel();
        $entityList = $category->getSeoUrls()
            ? $category->getSeoUrls()->filterBySalesChannelId($channel->getId())
            : null;

        return $entityList ? $this->getSeoUrl($channel, $entityList->first()) : '';
    }

    public function setIsAnchor(): void
    {
        parent::setIsAnchor($this->item->getDisplayNestedProducts());
    }

    /**
     * @param string $parentId
     *
     * @return $this
     */
    public function setParentId(string $parentId): CategoryMapping
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Set category image
     */
    public function setImage(): void
    {
        if ($this->item->getMediaId()) {
            $imageItem = new Shopgate_Model_Media_Image();
            $media = $this->item->getMedia();
            $imageItem->setUid($media ? $media->getId() : 1);
            $imageItem->setSortOrder(1);
            $imageItem->setUrl($media ? $media->getUrl() : '');
            $imageItem->setTitle($media && $media->getTitle() ? $media->getTitle() : $this->item->getName());
            $imageItem->setAlt($media && $media->getAlt() ? $media->getAlt() : '');

            parent::setImage($imageItem);
        }
    }

    /**
     * Set active state
     */
    public function setIsActive(): void
    {
        parent::setIsActive($this->item->getActive() && $this->item->getVisible());
    }
}
