<?php

namespace Shopgate\Shopware\Export\Catalog;

use Shopgate\Shopware\Export\Catalog\Mapping\CategoryMapping;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Category;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class Categories
{

    /**
     * @var LoggerInterface
     */
    private $log;
    /**
     * @var EntityRepositoryInterface
     */
    private $repository;

    public function __construct(LoggerInterface $logger, EntityRepositoryInterface $categoryRepository)
    {
        $this->log = $logger;
        $this->repository = $categoryRepository;
    }

    /**
     * @param array|null $ids
     * @param int|null $limit
     * @param int|null $offset
     * @return Shopgate_Model_Catalog_Category[]
     */
    public function buildCategoryTree(?array $ids, ?int $limit, ?int $offset): array
    {
        $parentId = $this->getRootCategoryId();
        $this->log->info('Build Tree with Parent-ID: ' . $parentId);

        $criteria = (new Criteria())
            ->setOffset($offset)
            ->setLimit($limit);

        if (empty($ids)) {
            $criteria = new Criteria([$parentId]);
            $criteria->addAssociation('children');
            /** @var CategoryCollection $result */
            $result = $this->repository->search($criteria, Context::createDefaultContext());
            //todo-sg: load children categories of parentId
            // use category depth limit?
            return $this->mapCategories($result->first()->getChildren());
        } else {
            $criteria->setIds($ids);
            /** @var CategoryCollection $result */
            $result = $this->repository->search($criteria, Context::createDefaultContext());
            return $this->mapCategories($result->first());
        }
    }

    private function getRootCategoryId(): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('category.parentId', null));
        $criteria->addSorting(new FieldSorting('category.createdAt', FieldSorting::ASCENDING));

        //todo-konstantin: pass down channel or context here
        $categories = $this->repository->searchIds($criteria, Context::createDefaultContext())->getIds();

        return array_shift($categories);
    }

    /**
     * @param CategoryCollection $collection
     * @return CategoryMapping[]
     */
    private function mapCategories(CategoryCollection $collection): array
    {
        $maxPosition = 100; //todo-konstantin: can I find this out?
        $export = [];
        foreach ($collection as $entity) {
            $this->log->info('Load Category with ID: ' . $entity->getId());
            //todo-konstantin: load category data from Shopware
            $categoryExportModel = new CategoryMapping();
            $categoryExportModel->setItem($entity);
            $categoryExportModel->setParentId($entity->getParentId());
            $categoryExportModel->setMaximumPosition($maxPosition);
            $export[] = $categoryExportModel->generateData();
        }

        return $export;
    }
}
