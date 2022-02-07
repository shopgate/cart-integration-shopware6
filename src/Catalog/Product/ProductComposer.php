<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product;

use Shopgate\Shopware\Catalog\Mapping\ProductMapFactory;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate_Model_Catalog_Product;
use Throwable;

class ProductComposer
{
    private LoggerInterface $logger;
    private ProductMapFactory $productMapFactory;
    private ProductBridge $productBridge;

    public function __construct(
        LoggerInterface $logger,
        ProductMapFactory $productMapFactory,
        ProductBridge $productBridge
    ) {
        $this->logger = $logger;
        $this->productMapFactory = $productMapFactory;
        $this->productBridge = $productBridge;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param array $uids
     * @return Shopgate_Model_Catalog_Product[]
     */
    public function loadProducts(?int $limit, ?int $offset, array $uids = []): array
    {
        $products = $this->productBridge->getProductList($limit, $offset, $uids);
        $list = [];
        foreach ($products as $product) {
            $shopgateProduct = $this->productMapFactory->createMapClass($product);
            $shopgateProduct->setItem($product);
            try {
                $list[] = $shopgateProduct->generateData();
            } catch (Throwable $exception) {
                $error = "Skipping export of product with id: {$product->getId()}
                    Message: {$exception->getMessage()},
                    Location: {$exception->getFile()}:{$exception->getLine()}";
                $this->logger->error($error);
                $this->logger->debug($error . "\n" . $exception->getTraceAsString());
            }
        }

        return $list;
    }
}
