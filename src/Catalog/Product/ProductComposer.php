<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product;

use Shopgate\Shopware\Catalog\Category\CategoryBridge;
use Shopgate\Shopware\Catalog\Mapping\ProductMapFactory;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate_Model_Catalog_Product;
use Throwable;

class ProductComposer
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ProductMapFactory $productMapFactory,
        private readonly ProductBridge $productBridge,
        private readonly CategoryBridge $categoryBridge
    ) {
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string[] $uids
     * @return Shopgate_Model_Catalog_Product[]
     */
    public function loadProducts(?int $limit, ?int $offset, array $uids = []): array
    {
        $products = $this->productBridge->getProductList($limit, $offset, $uids);
        $categoryProductMap = $this->categoryBridge->getCategoryProductMap($products->getIds());

        $list = [];
        foreach ($products as $product) {
            $shopgateProduct = $this->productMapFactory->createMapClass($product);
            $shopgateProduct->setItem($product);
            $shopgateProduct->setCategoryMap($categoryProductMap);
            try {
                $item = $shopgateProduct->generateData();
                $list[$item->getUid()] = $item;
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
