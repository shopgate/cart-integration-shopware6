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
        $parentId = $this->getRootCategoryId();
        $this->log->info('Build Tree with Parent-ID: ' . $parentId);
        $criteria = (new Criteria())
            ->setOffset($offset)
            ->setLimit($limit)
            ->addAssociation('media')
            ->addAssociation('seoUrls');

        if (empty($ids)) {
            $criteria->addFilter(
                new ContainsFilter('path', '|' . $parentId . '|'),
                new RangeFilter('level', [
                    RangeFilter::GT => 1,
                    RangeFilter::LTE => 99,
                ]),
                new EqualsFilter('active', 1),
                new EqualsFilter('visible', 1)
            );

            $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NEXT_PAGES);
            $list = $this->repository->search($criteria, $this->contextManager->getSalesContext()->getContext());
            return $this->mapCategories($list->getEntities()->getElements());
        }

        //$criteria->addFilter(new EqualsAnyFilter('id', $ids))
        $criteria->setIds($ids);
        $result = $this->repository->search($criteria, $this->contextManager->getSalesContext()->getContext());
        return $result->first() ? $this->mapCategories($result->first()->getChildren()) : [];
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
     * @param CategoryCollection|CategoryEntity[] $collection
     * @return CategoryMapping[]
     */
    private function mapCategories(array $collection): array
    {
        $maxPosition = 100;
        $export = [];
        foreach ($collection as $entity) {
            $this->log->info('Loading category with ID: ' . $entity->getId());
            $categoryExportModel = new CategoryMapping($this->contextManager);
            $categoryExportModel->setItem($entity);
            $categoryExportModel->setParentId($entity->getParentId());
            $categoryExportModel->setMaximumPosition($maxPosition);
            $export[] = $categoryExportModel->generateData();
        }

        return $export;
    }
}
