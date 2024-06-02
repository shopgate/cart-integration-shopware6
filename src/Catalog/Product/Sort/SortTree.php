<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Sort;

use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Shopgate\Shopware\Catalog\Category\CategoryBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\AbstractProductListRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\AbstractProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Adapter\Cache\CacheCompressor;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

class SortTree
{
    public const CACHE_KEY = 'shopgate.category.sort';

    public function __construct(
        private readonly ContextManager $contextManager,
        private readonly AbstractProductListingRoute $listingRoute,
    ) {
    }

    /**
     * Loops through all products for every category out there. Expensive stuff!
     */
    public function getAllCategoryProducts(CategoryEntity $category): ProductCollection
    {
        $list = new ProductCollection();
        $page = 1;
        $limit = 100;

        do {
            $request = new Request();
            $request->setMethod(Request::METHOD_POST);
            $request->request->set('p', $page++);
            $request->request->set('limit', $limit);
            $request->setSession(new Session()); // 3rd party subscriber support
            if ($orderKey = $this->getSortOrderKey($category)) {
                $request->request->set('order', $orderKey);
            }
            $criteria = new Criteria();
            $criteria->setTitle('shopgate::product::category-id');
            $result = $this->listingRoute
                ->load($category->getId(), $request, $this->contextManager->getSalesContext(), $criteria)
                ->getResult();
            $list->merge($result->getEntities());
            $pageCount = ceil($result->getTotal() / $limit);
        } while ($page <= $pageCount);

        return $list;
    }

    /**
     * Retrieves the default key to sort the category by
     *
     * @param CategoryEntity $category
     * @return string|null - e.g. price-asc, topseller
     */
    private function getSortOrderKey(CategoryEntity $category): ?string
    {
        if ($slot = (array)$category->getSlotConfig()) {
            $list = array_values($slot);
            if (is_array($list[0])) {
                $config = array_merge(...$list);
                if (isset($config['defaultSorting']['value'])) {
                    return $config['defaultSorting']['value'];
                }
            }
        }

        return null;
    }
}
