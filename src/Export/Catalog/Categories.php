<?php

namespace Shopgate\Shopware\Export\Catalog;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\Catalog\Mapping\CategoryMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Category;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class Categories
{
    /** @var LoggerInterface */
    private $log;
    /** @var EntityRepositoryInterface */
    private $repository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param LoggerInterface $logger
     * @param EntityRepositoryInterface $categoryRepository
     * @param ContextManager $contextManager
     */
    public function __construct(
        LoggerInterface $logger,
        EntityRepositoryInterface $categoryRepository,
        ContextManager $contextManager
    ) {
        $this->log = $logger;
        $this->repository = $categoryRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @param array|null $ids
     * @param int|null $limit
     * @param int|null $offset
     * @return Shopgate_Model_Catalog_Category[]
     * @throws MissingContextException
     */
    public function buildCategoryTree(?array $ids, ?int $limit, ?int $offset): array
    {
        $sliceOffset = ($offset - 1) * $limit;
        $parentId = $this->getRootCategoryId();
        $this->log->info('Build Tree with Parent-ID: ' . $parentId);
        $allCategories = $this->getChildCategories($parentId);

        if (empty($ids)) {
            $sliced = array_slice($allCategories->getElements(), $sliceOffset, $limit);
            return $this->mapCategories($sliced);
        }

        $filteredById = $allCategories->filter(function (CategoryEntity $item) use ($ids) {
            return in_array($item->getId(), $ids, true);
        });
        $sliced = array_slice($filteredById->getElements(), $sliceOffset, $limit);
        return $this->mapCategories($sliced);
    }

    /**
     * @return string
     * @throws MissingContextException
     */
    private function getRootCategoryId(): string
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
    private function getChildCategories(string $parentId): CategoryCollection
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

    /**
     * @param CategoryEntity[] $collection
     * @return CategoryMapping[]
     */
    private function mapCategories(array $collection): array
    {
        $export = [];
        foreach ($collection as $entity) {
            $this->log->info('Loading category with ID: ' . $entity->getId());
            $categoryExportModel = new CategoryMapping($this->contextManager);
            $categoryExportModel->setItem($entity);
            $categoryExportModel->setParentId($entity->getParentId());
            $export[] = $categoryExportModel->generateData();
        }

        return $export;
    }
}
