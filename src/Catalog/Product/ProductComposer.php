<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product;

use Shopgate\Shopware\Catalog\Mapping\ProductMapFactory;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate_Model_Catalog_Product;
use Throwable;

class ProductComposer
{
    /** @var LoggerInterface */
    private $logger;
    /** @var ProductMapFactory */
    private $productMapFactory;
    /** @var ProductBridge */
    private $productBridge;

    /**
     * @param LoggerInterface $logger
     * @param ProductMapFactory $productMapFactory
     * @param ProductBridge $productBridge
     * @param SortTree $sortTree
     */
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
     * @throws MissingContextException
     */
    public function loadProducts(?int $limit, ?int $offset, array $uids = []): array
    {
        $response = $this->productBridge->getProductList($limit, $offset, $uids);
        $list = [];
        foreach ($response->getProducts() as $product) {
            $shopgateProduct = $this->productMapFactory->createMapClass($product);
            $shopgateProduct->setItem($product);
            try {
                $list[] = $shopgateProduct->generateData();
            } catch (Throwable $exception) {
                $this->logger->error(
                    "Skipping export of product with id: {$product->getId()}, message: " . $exception->getMessage()
                );
            }
        }

        return $list;
    }
}
