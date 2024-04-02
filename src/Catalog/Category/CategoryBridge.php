<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Category;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\SalesChannel\AbstractCategoryListRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class CategoryBridge
{

    public function __construct(
        private readonly AbstractCategoryListRoute $categoryListRoute,
        private readonly ContextManager $contextManager
    ) {
    }

    public function getRootCategoryId(): string
    {
        return $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId();
    }

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
        $tree = $this->buildTree($parentId, $list);

        return $this->flattenTree($tree, new CategoryCollection());
    }

    private function buildTree(?string $parentId, CategoryCollection $categories): CategoryCollection
    {
        $children = new CategoryCollection();
        foreach ($categories as $key => $category) {
            if ($category->getParentId() !== $parentId) {
                continue;
            }
            $categories->remove($key);
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

    private function flattenTree(CategoryCollection $list, CategoryCollection $result): CategoryCollection
    {
        foreach ($list as $item) {
            if ($item->getChildren()) {
                $result = $this->flattenTree($item->getChildren(), $result);
            }
            $result->add($item);
        }
        return $result;
    }
}
