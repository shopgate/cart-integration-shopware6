<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\Components\Di\Facade;
use Shopgate\Shopware\Components\Di\Forwarder;
use Shopgate\Shopware\Exceptions\DiException;
use ShopgateCart;
use ShopgateCustomer;
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
    }

    public function cron($jobname, $params, &$message, &$errorcount)
    {
        // TODO: Implement cron() method.
    }

    public function getCustomer($user, $pass)
    {
        // TODO: Implement getCustomer() method.
    }

    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        // TODO: Implement registerCustomer() method.
    }

    public function addOrder(ShopgateOrder $order)
    {
        // TODO: Implement addOrder() method.
    }

    public function updateOrder(ShopgateOrder $order)
    {
        // TODO: Implement updateOrder() method.
    }

    public function checkCart(ShopgateCart $cart)
    {
        // TODO: Implement checkCart() method.
    }

    public function checkStock(ShopgateCart $cart)
    {
        // TODO: Implement checkStock() method.
    }

    public function getSettings()
    {
        // TODO: Implement getSettings() method.
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

    protected function createItems($limit = null, $offset = null, array $uids = array())
    {
        // TODO: Implement createItems() method.
    }

    /**
     * @inheritdoc
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
