<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Category;

use Shopgate\Shopware\Catalog\Mapping\CategoryMapping;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate_Model_AbstractExport;
use Shopgate_Model_Catalog_Category;
use Shopware\Core\Content\Category\CategoryEntity;

class CategoryComposer
{
    private LoggerInterface $log;
    private CategoryBridge $categoryBridge;
    /** @var CategoryMapping */
    private Shopgate_Model_AbstractExport $categoryMapping;

    public function __construct(
        LoggerInterface $logger,
        CategoryBridge $categoryBridge,
        Shopgate_Model_AbstractExport $categoryMapping
    ) {
        $this->log = $logger;
        $this->categoryBridge = $categoryBridge;
        $this->categoryMapping = $categoryMapping;
    }

    /**
     * @param array|null $ids
     * @param int|null $limit
     * @param int|null $offset
     * @return Shopgate_Model_Catalog_Category[]
     */
    public function buildCategoryTree(?array $ids, ?int $limit, ?int $offset): array
    {
        $sliceOffset = ($offset - 1) * $limit;
        $parentId = $this->categoryBridge->getRootCategoryId();
        $this->log->debug('Build Tree with Parent-ID: ' . $parentId);
        $allCategories = $this->categoryBridge->getChildCategories($parentId);

        if (!empty($ids)) {
            $allCategories->sortByIdArray($ids);
        }
        $sliced = array_slice($allCategories->getElements(), $sliceOffset, $limit);

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
            $categoryExportModel = clone $this->categoryMapping;
            /** @noinspection PhpParamsInspection */
            $categoryExportModel->setItem($entity);
            $categoryExportModel->setParentId($entity->getParentId());
            $export[] = $categoryExportModel->generateData();
        }

        return $export;
    }
}
