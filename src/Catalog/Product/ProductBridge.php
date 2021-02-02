<?php

namespace Shopgate\Shopware\Catalog\Product;

use Shopgate\Shopware\Catalog\Mapping\ProductMapFactory;
use Shopgate\Shopware\Catalog\Product\Sort\SortBridge;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\ProductListListRoute;
use Shopware\Core\Content\Product\SalesChannel\ProductListResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class ProductBridge
{
    /** @var ProductListListRoute */
    private $productListRoute;
    /** @var ContextManager */
    private $contextManager;
    /** @var SortBridge */
    private $productSorting;
    /** @var ConfigBridge */
    private $configReader;

    /**
     * @param LoggerInterface $logger
     * @param ProductListListRoute $productListRoute
     * @param ContextManager $contextManager
     * @param SortBridge $productSorting
     * @param ProductMapFactory $productMapFactory
     * @param ConfigBridge $configReader
     */
    public function __construct(
        ProductListListRoute $productListRoute,
        ContextManager $contextManager,
        SortBridge $productSorting,
        ConfigBridge $configReader
    ) {
        $this->productListRoute = $productListRoute;
        $this->contextManager = $contextManager;
        $this->productSorting = $productSorting;
        $this->configReader = $configReader;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param array $uids
     * @return ProductListResponse
     * @throws MissingContextException
     */
    public function getProductList(?int $limit, ?int $offset, array $uids = []): ProductListResponse
    {
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
                'children.seoUrls',
                'prices',
                'prices.rule'
            ])
            ->addSorting(...$this->productSorting->getDefaultSorting());

        // Simple products have child_count = 0, Children have 'null', Variants have 0+
        $types = $this->configReader->get('productTypesToExport');
        if (count($types) < 2) {
            $filter = [];
            if (!in_array(ConfigBridge::PROD_EXPORT_TYPE_SIMPLE, $types, true)) {
                $filter[] = new EqualsFilter('childCount', 0);
            }
            if (!in_array(ConfigBridge::PROD_EXPORT_TYPE_VARIANT, $types, true)) {
                $filter[] = new RangeFilter('childCount', [RangeFilter::GT => 0]);
            }
            $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, $filter));
        }

        return $this->productListRoute->load($criteria, $context);
    }
}
