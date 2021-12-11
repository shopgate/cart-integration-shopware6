<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Shopgate\RequestPersist;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate_Model_Catalog_Product;
use ShopgateCart;
use ShopgateCustomer;
use ShopgateLibraryException;
use ShopgateMerchantApiException;
use ShopgateOrder;
use ShopgatePlugin;

class Plugin extends ShopgatePlugin
{
    protected ExportService $exportService;
    protected ImportService $importService;
    protected LoggerInterface $logger;
    protected RequestPersist $requestPersist;

    /**
     * @required
     */
    public function dependencyInjector(
        ExportService $exportService,
        ImportService $importService,
        LoggerInterface $logger,
        RequestPersist $requestPersist
    ): void {
        $this->exportService = $exportService;
        $this->importService = $importService;
        $this->logger = $logger;
        $this->requestPersist = $requestPersist;
    }

    public function startup(): void
    {
        // NOTE! Everything here runs before dependencyInjector method
    }

    /**
     * @param string $jobname
     * @param $params
     * @param string $message
     * @param int $errorcount
     * @throws Exceptions\MissingContextException
     * @throws ShopgateLibraryException
     * @throws ShopgateMerchantApiException
     */
    public function cron($jobname, $params, &$message, &$errorcount): void
    {
        $this->exportService->cron($jobname, $this->builder->buildMerchantApi());
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
        return $this->exportService->getCustomer($user, $pass);
    }

    /**
     * @param string $user
     * @param string $pass
     * @param ShopgateCustomer $customer
     * @throws Exceptions\MissingContextException
     * @throws ShopgateLibraryException
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer): void
    {
        $this->importService->registerCustomer($user, $pass, $customer);
    }

    /**
     * @param ShopgateOrder $order
     * @return array
     * @throws Exceptions\MissingContextException
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order): array
    {
        $this->logger->debug('Incoming Add Order');
        $this->logger->debug(print_r($order->toArray(), true));
        $newOrder = (new ExtendedOrder())->loadFromShopgateOrder($order);
        $this->requestPersist->setIncomingOrder($newOrder);

        return $this->importService->addOrder($newOrder);
    }

    public function updateOrder(ShopgateOrder $order)
    {
        // TODO: Implement updateOrder() method.
    }

    /**
     * @param ShopgateCart $cart
     * @return array
     * @throws Exceptions\MissingContextException
     * @throws ShopgateLibraryException
     */
    public function checkCart(ShopgateCart $cart): array
    {
        $this->logger->debug('Incoming Check Cart');
        $this->logger->debug(print_r($cart->toArray(), true));
        $newCart = (new ExtendedCart())->loadFromShopgateCart($cart);

        $result = $this->exportService->checkCart($newCart);
        $this->logger->debug('Check Cart Response');
        $this->logger->debug((print_r($result, true)));
        return $result;
    }

    public function checkStock(ShopgateCart $cart)
    {
        // TODO: Implement checkStock() method.
    }

    /**
     * @return array[]
     * @throws Exceptions\MissingContextException
     */
    public function getSettings(): array
    {
        return $this->exportService->getSettings();
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
        return $this->exportService->getInfo();
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

        $products = $this->exportService->getProducts($limit, $offset, $uids);

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

        $categories = $this->exportService->getCategories($limit, $offset, $uids);
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
