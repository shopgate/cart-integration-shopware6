<?php

namespace Shopgate\Shopware\Catalog\Category;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class CategoryBridge
{
    /** @var EntityRepositoryInterface */
    private $repository;
    /** @var ContextManager */
    private $contextManager;

    public function __construct(EntityRepositoryInterface $categoryRepository, ContextManager $contextManager)
    {
        $this->repository = $categoryRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @return string
     * @throws MissingContextException
     */
    public function getRootCategoryId(): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('category.parentId', null));
        $criteria->addSorting(new FieldSorting('category.createdAt', FieldSorting::ASCENDING));

        $categories = $this->repository->searchIds(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->getIds();

        return array_shift($categories);
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
            ]),
            new EqualsFilter('active', 1),
            new EqualsFilter('visible', 1)
        );
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);
        $list = $this->repository->search($criteria, $this->contextManager->getSalesContext()->getContext());
        /** @var CategoryCollection $collection */
        $collection = $list->getEntities();
        $sorted = $collection->sortByPosition();
        $maxCategories = $collection->count();
        foreach ($sorted as $key => $entity) {
            $entity->setCustomFields(['sortOrder' => $maxCategories - $key]);
        }
        return $collection;
    }
}
