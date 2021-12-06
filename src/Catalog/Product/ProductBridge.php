<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product;

use Shopgate\Shopware\Catalog\Product\Events\AfterProductLoadEvent;
use Shopgate\Shopware\Catalog\Product\Events\BeforeProductLoadEvent;
use Shopgate\Shopware\Catalog\Product\Sort\SortBridge;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\AbstractProductListRoute;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProductBridge
{
    private AbstractProductListRoute $productListRoute;
    private ContextManager $contextManager;
    private SortBridge $productSorting;
    private ConfigBridge $configReader;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        AbstractProductListRoute $productListRoute,
        ContextManager $contextManager,
        SortBridge $productSorting,
        ConfigBridge $configReader,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->productListRoute = $productListRoute;
        $this->contextManager = $contextManager;
        $this->productSorting = $productSorting;
        $this->configReader = $configReader;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param array $uids
     * @return ProductCollection
     * @throws MissingContextException
     */
    public function getProductList(?int $limit, ?int $offset, array $uids = []): ProductCollection
    {
        $context = $this->contextManager->getSalesContext();
        $criteria = (new Criteria($uids))
            ->setLimit($limit)
            ->setOffset($offset)
            ->addFilter(new ProductAvailableFilter($context->getSalesChannel()->getId()))
            ->addFilter(new EqualsFilter('product.parentId', null))
            ->addAssociations([
                'crossSellings',
                'manufacturer',
                'media',
                'options',
                'prices',
                'prices.rule',
                'properties',
                'properties.group',
                'seoUrls',
                'visibilities',
                'variation',

                'children',
                'children.manufacturer',
                'children.media',
                'children.options',
                'children.prices',
                'children.prices.rule',
                'children.properties',
                'children.properties.group',
                'children.seoUrls',
                'children.unit'
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

        $this->eventDispatcher->dispatch(new BeforeProductLoadEvent($criteria, $context));

        $result = $this->productListRoute->load($criteria, $context)->getProducts();

        $this->eventDispatcher->dispatch(new AfterProductLoadEvent($result, $criteria, $context));

        return $result;
    }
}
