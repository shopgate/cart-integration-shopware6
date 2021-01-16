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
use Shopware\Core\Framework\Context;

class Plugin extends ShopgatePlugin
{
    /** @var Forwarder $forwarder */
    protected $forwarder;

    /** @var Context $context */
    protected $context;

    /**
     * @throws DiException
     */
    public function startup(): void
    {
        $this->forwarder = Facade::create(Forwarder::class);
    }

    /**
     * @param Context $context
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
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

    /**
     * @inheritdoc
     */
    protected function createCategories($limit = null, $offset = null, array $uids = []): array
    {
        if ($this->splittedExport) {
            $limit  = is_null($limit) ? $this->exportLimit : $limit;
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

    public function createPluginInfo(): array
    {
        try {
            $shopwareVersion = \PackageVersions\Versions::getVersion('shopware/core');
        } catch (OutOfBoundsException $e) {
            // shopware/core ist not installed
            $shopwareVersion = 'not available';
        }

        return ['Shopware core version' => $shopwareVersion];
    }
}
