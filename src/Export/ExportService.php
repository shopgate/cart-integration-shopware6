<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Export\Catalog\Categories;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Category;

class ExportService
{
    /**
     * @var LoggerInterface
     */
    private $log;
    /**
     * @var Categories
     */
    private $categoryHelper;
    /** @var ConfigExport */
    private $configExport;

    /**
     * @param LoggerInterface $logger
     * @param Categories $categoryHelper
     * @param ConfigExport $configExport
     */
    public function __construct(LoggerInterface $logger, Categories $categoryHelper, ConfigExport $configExport)
    {
        $this->log = $logger;
        $this->categoryHelper = $categoryHelper;
        $this->configExport = $configExport;
    }

    /**
     * @param null | string $limit
     * @param null | string $offset
     * @param string[] $ids
     * @return Shopgate_Model_Catalog_Category[]
     */
    public function getCategories($limit = null, $offset = null, array $ids = []): array
    {
        $this->log->info('Start Category Export...');

        $export = $this->categoryHelper->buildCategoryTree($ids, $limit, $offset);
        $this->log->info('End Category-Tree Build...');
        $this->log->info('Finished Category Export...');

        return $export;
    }

    /**
     *
     */
    public function definePluginVersion(): void
    {
        define('SHOPGATE_PLUGIN_VERSION', $this->configExport->getShopgatePluginVersion());
    }

    /**
     * @return string[]
     */
    public function getInfo(): array
    {
        return [
            'Shopware core version' => $this->configExport->getShopwareVersion()
        ];
    }
}
