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
        private readonly TagAwareAdapterInterface $cache,
        private readonly ContextManager $contextManager,
        private readonly CategoryBridge $categoryBridge,
        private readonly ConfigBridge $configReader,
        private readonly AbstractProductListRoute $listRoute,
        private readonly AbstractProductListingRoute $listingRoute,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * To enable cache you will need to go "prod" mode & enable HTTP Cache
     * @throws InvalidArgumentException|CacheException
     */
    public function getSortTree(string $rootCategoryId): array
    {
        $tree = $this->cache->getItem(self::CACHE_KEY . '.' . $rootCategoryId);
        try {
            if ($tree->isHit() && $tree->get()) {
                return CacheCompressor::uncompress($tree);
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
        }

        $this->logger->debug('Building new sort order cache');

        if ($this->configReader->get(ConfigBridge::SYSTEM_CONFIG_IGNORE_SORT_ORDER)) {
            $build = $this->buildWithoutPosition($rootCategoryId);
        } else {
            $build = $this->buildWithPosition($rootCategoryId);
        }

        $tree = CacheCompressor::compress($tree, $build);
        $tree->tag([self::CACHE_KEY]);
        $this->cache->save($tree);

        return $build;
    }

    /**
     * @param string $rootCategoryId - provide category id to build from
     * @return array - ['categoryId' => ['productId' => sortNumber]]
     */
    private function buildWithPosition(string $rootCategoryId): array
    {
        $tree = [];
        $categories = $this->categoryBridge->getChildCategories($rootCategoryId);
        foreach ($categories as $category) {
            $products = $this->getAllCategoryProducts($category);
            $maxProducts = $products->count();
            $i = 0;
            foreach ($products as $product) {
                $tree[$product->getParentId() ?: $product->getId()][] = [
                    'categoryId' => $category->getId(),
                    'position' => $maxProducts - $i++
                ];
            }
        }

        return $tree;
    }

    /**
     * @param string $rootCategoryId - provide category id to build from
     * @return array - ['categoryId' => ['productId']]
     */
    private function buildWithoutPosition(string $rootCategoryId): array
    {
        $tree = [];
        $categories = $this->categoryBridge->getChildCategories($rootCategoryId);
        $products = $this->getAllProducts();

        foreach ($products as $product) {

            foreach ($categories as $category) {

                $identifier = $product->getParentId() ?: $product->getId();
                if (array_key_exists($identifier, $tree)
                    && in_array($category->getId(), array_column($tree[$identifier], 'categoryId'), true)) {
                    continue;
                }

                if (($product->getCategoryTree() && in_array($category->getId(), $product->getCategoryTree(), true)
                        || $product->getStreamIds() && in_array($category->getProductStreamId(),
                            $product->getStreamIds(), true))
                    || $this->hasChildren($category, $product)) {

                    $tree[$identifier][] = ['categoryId' => $category->getId()];
                }
            }
        }

        return $tree;
    }

    /**
     * Loops through all products for every category out there. Expensive stuff!
     */
    private function getAllCategoryProducts(CategoryEntity $category): ProductCollection
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
     * Loops through all products for every category out there. Expensive stuff!
     */
    private function getAllProducts(): ProductCollection
    {
        $criteria = new Criteria();
        $criteria->setTitle('shopgate::product::all');

        return $this->listRoute->load($criteria, $this->contextManager->getSalesContext())->getProducts();
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

    private function hasChildren(CategoryEntity $category, SalesChannelProductEntity $product): bool
    {
        $hasChild = false;
        /** @var CategoryEntity $child */
        foreach ($category->getChildren() as $child) {

            if ($child->getChildren() && $child->getChildren()->count()) {
                $this->hasChildren($child, $product);
            }
            $hasChild =
                ($product->getCategoryTree() && in_array($child->getId(), $product->getCategoryTree(), true))
                || ($product->getStreamIds() && in_array($child->getProductStreamId(), $product->getStreamIds(), true));
        }

        return $hasChild;
    }
}
