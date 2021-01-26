<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\Catalog\Categories;
use Shopgate\Shopware\Export\Catalog\Products;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Catalog_Product;
use ShopgateCustomer;
use ShopgateLibraryException;

class ImportService
{
    /** @var LoggerInterface */
    private $log;
    /** @var Categories */
    private $categoryHelper;
    /** @var ConfigExport */
    private $configExport;
    /** @var CustomerExport */
    private $customerExport;
    /** @var TaxExport */
    private $taxExport;
    /** @var Customer */
    private $customerHelper;
    /** @var Products */
    private $productHelper;

    /**
     * @param LoggerInterface $logger
     * @param Categories $categoryHelper
     * @param ConfigExport $configExport
     * @param CustomerExport $customerExport
     * @param TaxExport $taxExport
     * @param Customer $customerHelper
     * @param Products $productHelper
     */
    public function __construct(
        LoggerInterface $logger,
        Categories $categoryHelper,
        ConfigExport $configExport,
        CustomerExport $customerExport,
        TaxExport $taxExport,
        Customer $customerHelper,
        Products $productHelper
    ) {
        $this->log = $logger;
        $this->categoryHelper = $categoryHelper;
        $this->configExport = $configExport;
        $this->customerExport = $customerExport;
        $this->taxExport = $taxExport;
        $this->customerHelper = $customerHelper;
        $this->productHelper = $productHelper;
    }

    /**
     * @param string $user
     * @param string $password
     * @param ShopgateCustomer $customer
     */
    public function registerCustomer(string $user, string $password, ShopgateCustomer $customer): void
    {
        //todo-rainer actually register the customer

//        no need for this, the library calls getCustomer anyway
//        return $this->customerHelper->getCustomerData($user, $password);

    }
}
