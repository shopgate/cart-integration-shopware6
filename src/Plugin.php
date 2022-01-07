<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\Exceptions\DiException;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\System\Di\Facade;
use Shopgate\Shopware\System\Di\Forwarder;
use Shopgate_Model_Catalog_Product;
use ShopgateCart;
use ShopgateCustomer;
use ShopgateLibraryException;
use ShopgateMerchantApiException;
use ShopgateOrder;
use ShopgatePlugin;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Plugin extends ShopgatePlugin
{
    protected ?Forwarder $forwarder;

    /**
     * @throws DiException
     */
    public function startup(): void
    {
        $this->forwarder = Facade::create(Forwarder::class);
        $this->forwarder->getExportService()->definePluginVersion();
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
        $this->forwarder->getExportService()->cron($jobname, $this->builder->buildMerchantApi());
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
    public function registerCustomer($user, $pass, ShopgateCustomer $customer): void
    {
        $this->forwarder->getImportService()->registerCustomer($user, $pass, $customer);
    }

    /**
     * @param ShopgateOrder $order
     * @return array
     * @throws Exceptions\MissingContextException
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order): array
    {
        $this->forwarder->getLogger()->debug('Incoming Add Order');
        $this->forwarder->getLogger()->debug(print_r($order->toArray(), true));
        $newOrder = (new ExtendedOrder())->loadFromShopgateOrder($order);
        $this->forwarder->getRequestPersist()->setIncomingOrder($newOrder);

        return $this->forwarder->getImportService()->addOrder($newOrder);
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
        $this->forwarder->getLogger()->debug('Incoming Check Cart');
        $this->forwarder->getLogger()->debug(print_r($cart->toArray(), true));
        $newCart = (new ExtendedCart())->loadFromShopgateCart($cart);

        $result = $this->forwarder->getExportService()->checkCart($newCart);
        $this->forwarder->getLogger()->debug('Check Cart Response');
        //todo: inject symfony/serializer once main branch is merged
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $this->forwarder->getLogger()->debug($serializer->serialize($result, 'json'));
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

    /**
     * @inerhitDoc
     * @throws Exceptions\MissingContextException
     */
    protected function createReviews($limit = null, $offset = null, array $uids = array()): array
    {
        if ($this->splittedExport) {
            $limit = is_null($limit) ? $this->exportLimit : $limit;
            $offset = is_null($offset) ? $this->exportOffset : $offset;
        }

        $reviews = $this->forwarder->getExportService()->getReviews($limit, $offset, $uids);
        foreach ($reviews as $review) {
            $this->addReviewModel($review);
        }

        return $reviews;
    }
}
