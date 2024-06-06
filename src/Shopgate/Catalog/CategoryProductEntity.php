<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Catalog;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class CategoryProductEntity extends Entity
{
    public string $salesChannelId;
    public string $productId;
    public string $productVersionId;
    public string $categoryId;
    public string $categoryVersionId;
    public int $sortOrder;

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
}
