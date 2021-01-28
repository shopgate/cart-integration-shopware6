<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\Catalog\Categories;
use Shopgate\Shopware\Export\Catalog\Products;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Catalog_Product;
use ShopgateCart;
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
    /** @var CustomerImport */
    private $customerImport;
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
     * @param CustomerImport $customerImport
     * @param TaxExport $taxExport
     * @param Customer $customerHelper
     * @param Products $productHelper
     */
    public function __construct(
        LoggerInterface $logger,
        Categories $categoryHelper,
        ConfigExport $configExport,
        CustomerImport $customerImport,
        TaxExport $taxExport,
        Customer $customerHelper,
        Products $productHelper
    ) {
        $this->log = $logger;
        $this->categoryHelper = $categoryHelper;
        $this->configExport = $configExport;
        $this->customerImport = $customerImport;
        $this->taxExport = $taxExport;
        $this->customerHelper = $customerHelper;
        $this->productHelper = $productHelper;
    }

    /**
     * @param string $user
     * @param string $password
     * @param ShopgateCustomer $customer
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function registerCustomer(string $user, string $password, ShopgateCustomer $customer): void
    {
        $this->customerImport->registerCustomer($user, $password, $customer);

    }

    /**
     * @param ShopgateCart $cart
     * @return array
     */
    public function checkCart(ShopgateCart $cart): array
    {
        // todo-rainer implement

        return [
            "currency"         => 'EUR',
            "external_coupons" => [],
            "shipping_methods" => [],
            "payment_methods"  => [],
            "items"            => [],
            "customer"         => new \ShopgateCartCustomer(),
        ];

    }
}
