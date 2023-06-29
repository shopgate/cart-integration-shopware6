<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Media_Image;
use Shopware\Core\Content\Category\CategoryEntity;

class CategoryMapping extends Shopgate_Model_Catalog_Category
{
    use MapTrait;

    /** @var CategoryEntity */
    protected $item;
    private ?string $parentId = null;
    private ContextManager $contextManager;

    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
        parent::__construct();
    }

    /**
     * Generate data dom object by firing a method array
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
        parent::setName($this->item->getTranslation('name'));
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
     */
    public function setDeeplink(): void
    {
        parent::setDeeplink($this->getDeepLinkUrl($this->item));
    }

    private function getDeepLinkUrl(CategoryEntity $category): string
    {
        $channel = $this->contextManager->getSalesContext();
        $entityList = $category->getSeoUrls()?->filterBySalesChannelId($channel->getSalesChannelId());

        return $entityList ? $this->getSeoUrl($channel, $entityList->first()) : '';
    }

    public function setIsAnchor(): void
    {
        parent::setIsAnchor($this->item->getDisplayNestedProducts());
    }

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
        if ($media = $this->item->getMedia()) {
            $imageItem = new Shopgate_Model_Media_Image();
            $imageItem->setUid($media->getId());
            $imageItem->setSortOrder(1);
            $imageItem->setUrl($media->getUrl());
            $imageItem->setTitle($media->getTitle() ?: $this->item->getName());
            $imageItem->setAlt($media->getAlt());

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

    public function getItem(): CategoryEntity
    {
        return $this->item;
    }
}
