<?php

namespace Shopgate\Shopware\Export\Catalog;

use Shopgate\Shopware\Components\ConfigManager\ConfigReaderInterface;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\Catalog\Mapping\ProductMapFactory;
use Shopgate\Shopware\Export\Catalog\Products\ProductSorting;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\Utility\LoggerInterface;
use Shopgate_Model_Catalog_Product;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\ProductListListRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Throwable;

class Products
{
    /** @var ProductListListRoute */
    private $productListRoute;
    /** @var ContextManager */
    private $contextManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var ProductSorting */
    private $productSorting;
    /** @var ProductMapFactory */
    private $productMapFactory;
    /** @var ConfigReaderInterface */
    private $configReader;

    /**
     * @param LoggerInterface $logger
     * @param ProductListListRoute $productListRoute
     * @param ContextManager $contextManager
     * @param ProductSorting $productSorting
     * @param ProductMapFactory $productMapFactory
     * @param ConfigReaderInterface $configReader
     */
    public function __construct(
        LoggerInterface $logger,
        ProductListListRoute $productListRoute,
        ContextManager $contextManager,
        ProductSorting $productSorting,
        ProductMapFactory $productMapFactory,
        ConfigReaderInterface $configReader
    ) {
        $this->logger = $logger;
        $this->productListRoute = $productListRoute;
        $this->contextManager = $contextManager;
        $this->productSorting = $productSorting;
        $this->productMapFactory = $productMapFactory;
        $this->configReader = $configReader;
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
            ->addFilter(new EqualsFilter('product.parentId', null))
            ->addAssociations([
                'media',
                'properties',
                'properties.group',
                'manufacturer',
                'options',
                'seoUrls',
                'categories',
                'visibilities',
                'variation',
                'children',
                'children.media',
                'children.options',
                'children.seoUrls'
            ])
            ->addSorting(...$this->productSorting->getDefaultSorting());

        // Simple products have child_count = 0, Children have 'null', Variants have 0+
        $types = $this->configReader->get('productTypesToExport');
        if (count($types) < 2) {
            $filter = [];
            if (!in_array(ConfigReaderInterface::PROD_EXPORT_TYPE_SIMPLE, $types, true)) {
                $filter[] = new EqualsFilter('childCount', 0);
            }
            if (!in_array(ConfigReaderInterface::PROD_EXPORT_TYPE_VARIANT, $types, true)) {
                $filter[] = new RangeFilter('childCount', [RangeFilter::GT => 0]);
            }
            $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, $filter));
        }
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
