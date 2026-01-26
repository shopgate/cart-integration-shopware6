<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Catalog;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\System\Language\LanguageEntity;

class CategoryProductEntity extends Entity
{
    public string $salesChannelId;
    public string $productId;
    public string $productVersionId;
    public string $categoryId;
    public string $categoryVersionId;
    public int $sortOrder;
    public string $languageId;

    public ?ProductEntity $product = null;
    public ?CategoryEntity $category = null;
    public ?LanguageEntity $language = null;

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): CategoryProductEntity
    {
        $this->salesChannelId = $salesChannelId;

        return $this;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): CategoryProductEntity
    {
        $this->productId = $productId;

        return $this;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function setCategoryId(string $categoryId): CategoryProductEntity
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function setProductVersionId(string $productVersionId): CategoryProductEntity
    {
        $this->productVersionId = $productVersionId;

        return $this;
    }

    public function getProductVersionId(): string
    {
        return $this->productVersionId;
    }

    public function setCategoryVersionId(string $categoryVersionId): CategoryProductEntity
    {
        $this->categoryVersionId = $categoryVersionId;

        return $this;
    }

    public function getCategoryVersionId(): string
    {
        return $this->categoryVersionId;
    }

    public function setSortOrder(int $sortOrder): CategoryProductEntity
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    public function setLanguageId(string $languageId): CategoryProductEntity
    {
        $this->languageId = $languageId;

        return $this;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): CategoryProductEntity
    {
        $this->product = $product;

        return $this;
    }

    public function getCategory(): ?CategoryEntity
    {
        return $this->category;
    }

    public function setCategory(?CategoryEntity $category): CategoryProductEntity
    {
        $this->category = $category;

        return $this;
    }

    public function getLanguage(): ?LanguageEntity
    {
        return $this->language;
    }

    public function setLanguage(?LanguageEntity $language): CategoryProductEntity
    {
        $this->language = $language;

        return $this;
    }
}
