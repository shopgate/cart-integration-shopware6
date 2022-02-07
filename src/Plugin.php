<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Shopgate\RequestPersist;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate_Model_Catalog_Product;
use ShopgateCart;
use ShopgateCustomer;
use ShopgateLibraryException;
use ShopgateMerchantApiException;
use ShopgateOrder;
use ShopgatePlugin;
use Shopware\Core\Framework\Uuid\Uuid;

class Plugin extends ShopgatePlugin
{
    protected ExportService $exportService;
    protected ImportService $importService;
    protected LoggerInterface $logger;
    protected RequestPersist $requestPersist;
    protected ExtendedClassFactory $classFactory;

    /**
     * @required
     */
    public function dependencyInjector(
        ExportService $exportService,
        ImportService $importService,
        LoggerInterface $logger,
        RequestPersist $requestPersist,
        ExtendedClassFactory $classFactory
    ): void {
        $this->exportService = $exportService;
        $this->importService = $importService;
        $this->logger = $logger;
        $this->requestPersist = $requestPersist;
        $this->classFactory = $classFactory;
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
     * @throws ShopgateLibraryException
     */
    public function cron($jobname, $params, &$message, &$errorcount): void
    {
        $this->exportService->cron($jobname);
    }

    /**
     * @param string $user
     * @param string $pass
     * @return ShopgateCustomer
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
     * @throws ShopgateLibraryException
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer): void
    {
        $this->importService->registerCustomer($user, $pass, $customer);
    }

    /**
     * @param ShopgateOrder $order
     * @return array
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order): array
    {
        $this->logger->debug('Incoming Add Order');
        $this->logger->debug($order);
        $newOrder = $this->classFactory->createOrder()->loadFromShopgateOrder($order);
        $this->requestPersist->setIncomingOrder($newOrder);

        return $this->importService->addOrder($newOrder);
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function updateOrder(ShopgateOrder $order): array
    {
        $this->logger->debug('Incoming Update Order');
        $this->logger->debug($order);

        return $this->importService->updateOrder($order);
    }

    /**
     * @param ShopgateCart $cart
     * @return array
     * @throws ShopgateLibraryException
     */
    public function checkCart(ShopgateCart $cart): array
    {
        $this->logger->debug('Incoming Check Cart');
        $this->logger->debug($cart);
        $newCart = $this->classFactory->createCart()->loadFromShopgateCart($cart);

        $result = $this->exportService->checkCart($newCart);
        $this->logger->debug('Check Cart Response');
        $this->logger->debug($result);

        return $result;
    }

    public function checkStock(ShopgateCart $cart)
    {
        // TODO: Implement checkStock() method.
    }

    /**
     * @return array[]
     */
    public function getSettings(): array
    {
        return $this->exportService->getSettings();
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function getOrders(
        $customerToken,
        $customerLanguage,
        $limit = 10,
        $offset = 0,
        $orderDateFrom = '',
        $sortOrder = 'created_desc'
    ): array {
        if (empty($customerToken) || false === Uuid::isValid($customerToken)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CUSTOMER_TOKEN_INVALID);
        }
        return $this->exportService->getOrders($customerToken, (int)$limit, (int)$offset, $sortOrder, $orderDateFrom);
    }

    public function syncFavouriteList($customerToken, $items)
    {
        // TODO: Implement syncFavouriteList() method.
    }

    public function createPluginInfo(): array
    {
        return $this->exportService->getInfo();
    }

    protected function createMediaCsv(): void
    {
        // TODO: Implement createMediaCsv() method.
    }

    /**
     * @param null $limit
     * @param null $offset
     * @param array $uids
     * @return Shopgate_Model_Catalog_Product[]
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

    /**
     * @inerhitDoc
     */
    protected function createReviews($limit = null, $offset = null, array $uids = array()): array
    {
        if ($this->splittedExport) {
            $limit = is_null($limit) ? $this->exportLimit : $limit;
            $offset = is_null($offset) ? $this->exportOffset : $offset;
        }

        $reviews = $this->exportService->getReviews($limit, $offset, $uids);
        foreach ($reviews as $review) {
            $this->addReviewModel($review);
        }

        return $reviews;
    }
}
