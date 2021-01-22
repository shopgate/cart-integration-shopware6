<?php

namespace Shopgate\Shopware\Export\Catalog;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\Catalog\Mapping\ProductMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Product;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\ProductListListRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Throwable;

class Products
{
    /**
     * @var ProductListListRoute
     */
    private $productListRoute;
    /**
     * @var ContextManager
     */
    private $contextManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param ProductListListRoute $productListRoute
     * @param ContextManager $contextManager
     */
    public function __construct(
        LoggerInterface $logger,
        ProductListListRoute $productListRoute,
        ContextManager $contextManager
    ) {
        $this->logger = $logger;
        $this->productListRoute = $productListRoute;
        $this->contextManager = $contextManager;
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
        $context = $this->contextManager->getSalesContext();
        $criteria = (new Criteria($uids))
            ->setLimit($limit)
            ->setOffset($offset)
            ->addFilter(
                new ProductAvailableFilter($context->getSalesChannel()->getId())
            )
            ->addAssociation('media')
            ->addAssociation('properties')
            ->addAssociation('options')
            ->addAssociation('seoUrls')
            ->addAssociation('categories');
        $response = $this->productListRoute->load($criteria, $context);
        $list = [];
        foreach ($response->getProducts() as $product) {
            $shopgateProduct = new ProductMapping($this->contextManager);
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
