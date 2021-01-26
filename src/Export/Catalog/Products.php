<?php

namespace Shopgate\Shopware\Export\Catalog;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\Catalog\Mapping\ProductMapFactory;
use Shopgate\Shopware\Export\Catalog\Products\ProductSorting;
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
    /** @var ProductSorting */
    private $productSorting;
    /** @var ProductMapFactory */
    private $productMapFactory;

    /**
     * @param LoggerInterface $logger
     * @param ProductListListRoute $productListRoute
     * @param ContextManager $contextManager
     * @param ProductSorting $productSorting
     * @param ProductMapFactory $productMapFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ProductListListRoute $productListRoute,
        ContextManager $contextManager,
        ProductSorting $productSorting,
        ProductMapFactory $productMapFactory
    ) {
        $this->logger = $logger;
        $this->productListRoute = $productListRoute;
        $this->contextManager = $contextManager;
        $this->productSorting = $productSorting;
        $this->productMapFactory = $productMapFactory;
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
        $minSortOrder = max($offset - 1, 0) * $limit;
        $context = $this->contextManager->getSalesContext();
        $criteria = (new Criteria($uids))
            ->setLimit($limit)
            ->setOffset($offset)
            ->addFilter(new ProductAvailableFilter($context->getSalesChannel()->getId()))
            ->addAssociations([
                'media',
                'properties',
                'properties.group',
                'options',
                'seoUrls',
                'categories',
                'visibilities',
                'variation',
                'children',
                'children.options'
            ])
            ->addSorting(...$this->productSorting->getDefaultSorting());
        $response = $this->productListRoute->load($criteria, $context);
        $list = [];
        $i = 0;
        foreach ($response->getProducts() as $product) {
            $sortOrder = 1000000 - ($minSortOrder + $i++);
            $shopgateProduct = $this->productMapFactory->createMapClass($product, $sortOrder);
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
