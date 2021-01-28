<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\Components\Di\Facade;
use Shopgate\Shopware\Components\Di\Forwarder;
use Shopgate\Shopware\Exceptions\DiException;
use Shopgate_Model_Catalog_Product;
use ShopgateCart;
use ShopgateCustomer;
use ShopgateLibraryException;
use ShopgateOrder;
use ShopgatePlugin;

class Plugin extends ShopgatePlugin
{
    /** @var Forwarder $forwarder */
    protected $forwarder;

    /**
     * @throws DiException
     */
    public function startup(): void
    {
        $this->forwarder = Facade::create(Forwarder::class);
        $this->forwarder->getExportService()->definePluginVersion();
    }

    public function cron($jobname, $params, &$message, &$errorcount)
    {
        // TODO: Implement cron() method.
    }

    /**
     * @param string $user
     * @param string $pass
     * @return ShopgateCustomer
     * @throws Exceptions\MissingContextException
     * @throws ShopgateLibraryException
     */
    public function getCustomer($user, $pass): ShopgateCustomer
    {
        return $this->forwarder->getExportService()->getCustomer($user, $pass);
    }

    /**
     * @param string $user
     * @param string $pass
     * @param ShopgateCustomer $customer
     * @throws Exceptions\MissingContextException
     * @throws ShopgateLibraryException
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        $this->forwarder->getImportService()->registerCustomer($user, $pass, $customer);
    }

    public function addOrder(ShopgateOrder $order)
    {
        // TODO: Implement addOrder() method.
    }

    public function updateOrder(ShopgateOrder $order)
    {
        // TODO: Implement updateOrder() method.
    }

    /**
     * @param ShopgateCart $cart
     * @return array
     */
    public function checkCart(ShopgateCart $cart)
    {
        return $this->forwarder->getImportService()->checkCart($cart);
    }

    public function checkStock(ShopgateCart $cart)
    {
        // TODO: Implement checkStock() method.
    }

    /**
     * @return array[]
     * @throws Exceptions\MissingContextException
     */
    public function getSettings()
    {
        return $this->forwarder->getExportService()->getSettings();
    }

    public function getOrders(
        $customerToken,
        $customerLanguage,
        $limit = 10,
        $offset = 0,
        $orderDateFrom = '',
        $sortOrder = 'created_desc'
    ) {
        // TODO: Implement getOrders() method.
    }

    public function syncFavouriteList($customerToken, $items)
    {
        // TODO: Implement syncFavouriteList() method.
    }

    public function createPluginInfo(): array
    {
        return $this->forwarder->getExportService()->getInfo();
    }

    protected function createMediaCsv()
    {
        // TODO: Implement createMediaCsv() method.
    }

    /**
     * @param null $limit
     * @param null $offset
     * @param array $uids
     * @return Shopgate_Model_Catalog_Product[]
     * @throws Exceptions\MissingContextException
     */
    protected function createItems($limit = null, $offset = null, array $uids = array()): array
    {
        if ($this->splittedExport) {
            $limit = is_null($limit) ? $this->exportLimit : $limit;
            $offset = is_null($offset) ? $this->exportOffset : $offset;
        }

        $products = $this->forwarder->getExportService()->getProducts($limit, $offset, $uids);

        foreach ($products as $product) {
            $this->addItemModel($product);
        }

        return $products;
    }

    /**
     * @inheritdoc
     * @throws Exceptions\MissingContextException
     */
    protected function createCategories($limit = null, $offset = null, array $uids = []): array
    {
        if ($this->splittedExport) {
            $limit = is_null($limit) ? $this->exportLimit : $limit;
            $offset = is_null($offset) ? $this->exportOffset : $offset;
        }

        $categories = $this->forwarder->getExportService()->getCategories($limit, $offset, $uids);

        foreach ($categories as $category) {
            $this->addCategoryModel($category);
        }

        return $categories;
    }

    protected function createReviews($limit = null, $offset = null, array $uids = array())
    {
        // TODO: Implement createReviews() method.
    }
}
