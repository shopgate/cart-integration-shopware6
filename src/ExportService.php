<?php

namespace Shopgate\Shopware;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Catalog\Categories;
use Shopgate\Shopware\Catalog\Products;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Customer\CustomerBridge;
use Shopgate\Shopware\System\Tax\TaxComposer;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Catalog_Product;
use ShopgateCustomer;
use ShopgateLibraryException;

class ExportService
{
    /** @var LoggerInterface */
    private $log;
    /** @var Categories */
    private $categoryHelper;
    /** @var ConfigBridge */
    private $configExport;
    /** @var CustomerBridge */
    private $customerBridge;
    /** @var TaxComposer */
    private $taxComposer;
    /** @var CustomerComposer */
    private $customerComposer;
    /** @var Products */
    private $productHelper;

    /**
     * @param LoggerInterface $logger
     * @param Categories $categoryHelper
     * @param ConfigBridge $configExport
     * @param CustomerBridge $customerBridge
     * @param TaxComposer $taxComposer
     * @param CustomerComposer $customerHelper
     * @param Products $productHelper
     */
    public function __construct(
        LoggerInterface $logger,
        Categories $categoryHelper,
        ConfigBridge $configExport,
        CustomerBridge $customerBridge,
        TaxComposer $taxComposer,
        CustomerComposer $customerHelper,
        Products $productHelper
    ) {
        $this->log = $logger;
        $this->categoryHelper = $categoryHelper;
        $this->configExport = $configExport;
        $this->customerBridge = $customerBridge;
        $this->taxComposer = $taxComposer;
        $this->customerComposer = $customerHelper;
        $this->productHelper = $productHelper;
    }

    /**
     * @param null | string $limit
     * @param null | string $offset
     * @param string[] $ids
     * @return Shopgate_Model_Catalog_Category[]
     * @throws MissingContextException
     */
    public function getCategories(int $limit = null, int $offset = null, array $ids = []): array
    {
        $this->log->info('Start Category Export...');

        $export = $this->categoryHelper->buildCategoryTree($ids, $limit, $offset);
        $this->log->info('End Category-Tree Build...');
        $this->log->info('Finished Category Export...');

        return $export;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param array $ids
     * @return Shopgate_Model_Catalog_Product[]
     * @throws MissingContextException
     */
    public function getProducts(int $limit = null, int $offset = null, array $ids = []): array
    {
        $this->log->info('Start Product Export...');
        $export = $this->productHelper->loadProducts($limit, $offset, $ids);
        $this->log->info('Finished Product Export...');

        return $export;
    }

    /**
     * @param string $user
     * @param string $password
     * @return ShopgateCustomer
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function getCustomer(string $user, string $password): ShopgateCustomer
    {
        return $this->customerComposer->getCustomer($user, $password);
    }

    /**
     * Sets plugin version globally before action handler runs
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

    /**
     * @return array[]
     * @throws MissingContextException
     */
    public function getSettings(): array
    {
        return [
            'customer_groups' => $this->customerBridge->getCustomerGroups(),
            'tax' => $this->taxComposer->getTaxSettings(),
            'allowed_address_countries' => [],
            'allowed_shipping_countries' => [],
            'payment_methods' => [],
        ];
    }
}
