<?php

namespace Shopgate\Shopware\Catalog\Category;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Category\SalesChannel\CategoryListRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class CategoryBridge
{
    /** @var CategoryListRoute */
    private $categoryListRoute;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param CategoryListRoute $categoryListRoute
     * @param ContextManager $contextManager
     */
    public function __construct(
        CategoryListRoute $categoryListRoute,
        ContextManager $contextManager
    ) {
        $this->categoryListRoute = $categoryListRoute;
        $this->contextManager = $contextManager;
    }

    /**
     * @return string
     * @throws MissingContextException
     */
    public function getRootCategoryId(): string
    {
        return $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId();
    }

    /**
     * @param string $parentId
     * @return CategoryCollection
     * @throws MissingContextException
     */
    public function getChildCategories(string $parentId): CategoryCollection
    {
        $criteria = (new Criteria())
            ->addAssociation('media')
            ->addAssociation('seoUrls');
        $criteria->addFilter(
            new ContainsFilter('path', '|' . $parentId . '|'),
            new RangeFilter('level', [
                RangeFilter::GT => 1,
                RangeFilter::LTE => 99,
            ])
        );
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);
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
        foreach ($children as $key => $child) {
            $child->setChildren($this->buildTree($child->getId(), $categories));
            $child->setCustomFields(['sortOrder' => $maxChildren - $key]);
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
