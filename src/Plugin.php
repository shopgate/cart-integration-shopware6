<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use ShopgateCart;
use ShopgateCartItem;
use ShopgateCustomer;
use ShopgateExternalOrder;
use ShopgateLibraryException;
use ShopgateOrder;
use ShopgatePlugin;
use ShopgateSyncItem;

class Plugin extends ShopgatePlugin
{

    public function startup()
    {
        // TODO: Implement startup() method.
        if (!defined("SHOPGATE_PLUGIN_VERSION")) {
            define("SHOPGATE_PLUGIN_VERSION", 'dummy version plugin');
        }
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

    protected function createMediaCsv()
    {
        // TODO: Implement createMediaCsv() method.
    }

    public function getOrders($customerToken, $customerLanguage, $limit = 10, $offset = 0, $orderDateFrom = '', $sortOrder = 'created_desc')
    {
        // TODO: Implement getOrders() method.
    }

    public function syncFavouriteList($customerToken, $items)
    {
        // TODO: Implement syncFavouriteList() method.
    }

    protected function createItems($limit = null, $offset = null, array $uids = array())
    {
        // TODO: Implement createItems() method.
    }

    protected function createCategories($limit = null, $offset = null, array $uids = array())
    {
        // TODO: Implement createCategories() method.
    }

    protected function createReviews($limit = null, $offset = null, array $uids = array())
    {
        // TODO: Implement createReviews() method.
    }

    public function createPluginInfo()
    {
        return['Shopware version' => 'dummy version shopware'];
    }

    public function createShopInfo()
    {
        return[
            'category_count' => 'dummy count category',
            'item_count'     => 'dummy count item',
            'review_count'   => 'dummy count review'
            ];
    }
}