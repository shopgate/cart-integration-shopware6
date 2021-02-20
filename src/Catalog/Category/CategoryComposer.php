<?php

namespace Shopgate\Shopware\Catalog\Category;

use Shopgate\Shopware\Catalog\Mapping\CategoryMapping;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate_Model_Catalog_Category;
use Shopware\Core\Content\Category\CategoryEntity;

class CategoryComposer
{
    /** @var LoggerInterface */
    private $log;
    /** @var CategoryBridge */
    private $categoryBridge;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param LoggerInterface $logger
     * @param CategoryBridge $categoryBridge
     * @param ContextManager $contextManager
     */
    public function __construct(LoggerInterface $logger, CategoryBridge $categoryBridge, ContextManager $contextManager)
    {
        $this->log = $logger;
        $this->categoryBridge = $categoryBridge;
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
        $parentId = $this->categoryBridge->getRootCategoryId();
        $this->log->debug('Build Tree with Parent-ID: ' . $parentId);
        $allCategories = $this->categoryBridge->getChildCategories($parentId);

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
     * @param CategoryEntity[] $collection
     * @return CategoryMapping[]
     */
    private function mapCategories(array $collection): array
    {
        $export = [];
        foreach ($collection as $entity) {
            $this->log->debug('Loading category with ID: ' . $entity->getId());
            $categoryExportModel = new CategoryMapping($this->contextManager);
            $categoryExportModel->setItem($entity);
            $categoryExportModel->setParentId($entity->getParentId());
            $export[] = $categoryExportModel->generateData();
        }

        return $export;
    }
}
