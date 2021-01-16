<?php

namespace Shopgate\Shopware\Export\Catalog\Mapping;

use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Media_Image;
use Shopware\Core\Content\Category\CategoryEntity;

class CategoryMapping extends Shopgate_Model_Catalog_Category
{
    /** @var CategoryEntity */
    protected $item;
    /** @var null */
    protected $parentId;
    /** @var null | int */
    protected $maxPosition;

    /**
     * @param int $position - position of the max category element
     *
     * @return $this
     */
    public function setMaximumPosition(int $position): CategoryMapping
    {
        $this->maxPosition = $position;

        return $this;
    }

    /**
     * @return null | int
     */
    public function getMaximumPosition(): ?int
    {
        return $this->maxPosition;
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
     * Set category id
     */
    public function setUid(): void
    {
        parent::setUid($this->item->getId());
    }

    /**
     * Set category sort order
     */
    public function setSortOrder(): void
    {
        // todo-konstantin: figure out category sort position
        parent::setSortOrder($this->getMaximumPosition() - 0);
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
        parent::setParentUid($this->item->getParentId() !== $this->parentId ? $this->item->getParentId() : null);
    }

    /**
     * Category link in shop
     */
    public function setDeeplink(): void
    {
        parent::setDeeplink($this->getDeepLinkUrl($this->item));
    }

    /**
     * Check if category is anchor
     */
    public function setIsAnchor(): void
    {
        //todo-konstantin: what was an anchor?
        parent::setIsAnchor();
    }

    /**
     * @param $parentId
     *
     * @return $this
     */
    public function setParentId($parentId): CategoryMapping
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * @param CategoryEntity $category
     * @return string
     */
    private function getDeepLinkUrl(CategoryEntity $category): string
    {
        //todo-konstantin get salesChannel here
        $seo = $category->getSeoUrls();

        return $seo ? $seo->filterBySalesChannelId(0) : '';
    }

    /**
     * Set category image
     */
    public function setImage(): void
    {
        if ($this->item->getMediaId()) {
            $imageItem = new Shopgate_Model_Media_Image();
            $media = $this->item->getMedia();
            $imageItem->setUid(1);
            $imageItem->setSortOrder(1);
            $imageItem->setUrl($media ? $media->getUrl() : '');
            $imageItem->setTitle($this->item->getName());

            parent::setImage($imageItem);
        }
    }

    /**
     * Set active state
     */
    public function setIsActive(): void
    {
        $isActive = $this->item->getActive();
        $isActive = $this->isActiveForceRewrite($isActive);

        parent::setIsActive($isActive);
    }

    /**
     * Checks if the category is forced to be enabled by merchant
     * via the Stores > Config value
     *
     * @param int $isActive
     *
     * @return int
     */
    private function isActiveForceRewrite(int $isActive): int
    {
        if ($isActive === 1) {
            return $isActive;
        }

        // todo: get forced cat id's from config
        $catIds      = [];
        $catIdsArray = array_map('trim', explode(',', $catIds));

        if (empty($catIds)) {
            return $isActive;
        }

//        if ((in_array($this->item->getId(), $catIdsArray, true)
//            || array_intersect(
//                $catIdsArray,
//                $this->item->getParentIds()
//            ))
//        ) {
//            $isActive = 1;
//        }

        return $isActive;
    }
}
