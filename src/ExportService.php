<?php

namespace Shopgate\Shopware;

use Shopgate\Shopware\Catalog\Category\CategoryComposer;
use Shopgate\Shopware\Catalog\Product\ProductComposer;
use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate\Shopware\System\Tax\TaxComposer;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Catalog_Product;
use ShopgateCustomer;
use ShopgateLibraryException;

class ExportService
{
    /** @var LoggerInterface */
    private $log;
    /** @var CategoryComposer */
    private $categoryComposer;
    /** @var ConfigBridge */
    private $configBridge;
    /** @var TaxComposer */
    private $taxComposer;
    /** @var CustomerComposer */
    private $customerComposer;
    /** @var ProductComposer */
    private $productComposer;

    /**
     * @param LoggerInterface $logger
     * @param CategoryComposer $categoryComposer
     * @param ConfigBridge $configBridge
     * @param TaxComposer $taxComposer
     * @param CustomerComposer $customerHelper
     * @param ProductComposer $productComposer
     */
    public function __construct(
        LoggerInterface $logger,
        CategoryComposer $categoryComposer,
        ConfigBridge $configBridge,
        TaxComposer $taxComposer,
        CustomerComposer $customerHelper,
        ProductComposer $productComposer
    ) {
        $this->log = $logger;
        $this->categoryComposer = $categoryComposer;
        $this->configBridge = $configBridge;
        $this->taxComposer = $taxComposer;
        $this->customerComposer = $customerHelper;
        $this->productComposer = $productComposer;
    }

    /**
     * @param null | int $limit
     * @param null | int $offset
     * @param string[] $ids
     * @return Shopgate_Model_Catalog_Category[]
     * @throws MissingContextException
     */
    public function getCategories(int $limit = null, int $offset = null, array $ids = []): array
    {
        $this->log->info('Start Category Export...');

        $export = $this->categoryComposer->buildCategoryTree($ids, $limit, $offset);
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
        $export = $this->productComposer->loadProducts($limit, $offset, $ids);
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
        define('SHOPGATE_PLUGIN_VERSION', $this->configBridge->getShopgatePluginVersion());
    }

    /**
     * @return string[]
     */
    public function getInfo(): array
    {
        return [
            'Shopware core version' => $this->configBridge->getShopwareVersion()
        ];
    }

    /**
     * @return array[]
     * @throws MissingContextException
     */
    public function getSettings(): array
    {
        return [
            'customer_groups' => $this->customerComposer->getCustomerGroups(),
            'tax' => $this->taxComposer->getTaxSettings(),
            'allowed_address_countries' => [],
            'allowed_shipping_countries' => [],
            'payment_methods' => [],
        ];
    }
}