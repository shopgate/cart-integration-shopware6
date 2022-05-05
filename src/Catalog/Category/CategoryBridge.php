<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Category;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Category\SalesChannel\AbstractCategoryListRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class CategoryBridge
{
    private AbstractCategoryListRoute $categoryListRoute;
    private ContextManager $contextManager;

    /**
     * @param AbstractCategoryListRoute $categoryListRoute
     * @param ContextManager $contextManager
     */
    public function __construct(
        AbstractCategoryListRoute $categoryListRoute,
        ContextManager $contextManager
    ) {
        $this->categoryListRoute = $categoryListRoute;
        $this->contextManager = $contextManager;
    }

    /**
     * @return string
     */
    public function getRootCategoryId(): string
    {
        return $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId();
    }

    /**
     * @param string $parentId
     * @return CategoryCollection
     */
    public function getChildCategories(string $parentId): CategoryCollection
    {
        $criteria = (new Criteria())
            ->addAssociation('media')
            ->addAssociation('seoUrls')
            ->addFilter(
                new ContainsFilter('path', '|' . $parentId . '|'),
                new RangeFilter('level', [
                    RangeFilter::GT => 1,
                    RangeFilter::LTE => 99,
                ])
            );
        $criteria->setTitle('shopgate::category::parent-id');
        $list = $this->categoryListRoute->load($criteria, $this->contextManager->getSalesContext())->getCategories();
        $tree = $this->buildTree($parentId, $list->getElements());
        $flatten = $this->flattenTree($tree->getElements());

        return new CategoryCollection($flatten);
    }

    /**
     * @param string|null $parentId
     * @param array $categories
     * @return CategoryCollection
     */
    private function buildTree(?string $parentId, array $categories): CategoryCollection
    {
        $children = new CategoryCollection();
        foreach ($categories as $key => $category) {
            if ($category->getParentId() !== $parentId) {
                continue;
            }
            unset($categories[$key]);

            $children->add($category);
        }

        $children->sortByPosition();

        $items = new CategoryCollection();
        $maxChildren = $children->count();
        $i = 0;
        foreach ($children as $child) {
            $child->setChildren($this->buildTree($child->getId(), $categories));
            $child->setCustomFields(['sortOrder' => $maxChildren - $i++]);
            $items->add($child);
        }

        return $items;
    }

    /**
     * @param CategoryEntity[] $list
     * @param CategoryEntity[] $result
     * @return array
     */
    private function flattenTree(array $list, array $result = []): array
    {
        foreach ($list as $item) {
            if ($item->getChildren()) {
                $result = $this->flattenTree($item->getChildren()->getElements(), $result);
            }
            $result[] = $item;
        }
        return $result;
    }
}
