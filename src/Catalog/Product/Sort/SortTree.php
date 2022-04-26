<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Sort;

use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Shopgate\Shopware\Catalog\Category\CategoryBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\AbstractProductListingRoute;
use Shopware\Core\Framework\Adapter\Cache\CacheCompressor;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

class SortTree
{
    public const CACHE_KEY = 'shopgate.category.sort';
    private ContextManager $contextManager;
    private CategoryBridge $categoryBridge;
    private AbstractProductListingRoute $listingRoute;
    private TagAwareAdapterInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        TagAwareAdapterInterface $cache,
        ContextManager $contextManager,
        CategoryBridge $categoryBridge,
        AbstractProductListingRoute $listingRoute,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->contextManager = $contextManager;
        $this->categoryBridge = $categoryBridge;
        $this->listingRoute = $listingRoute;
        $this->logger = $logger;
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
        $build = $this->build($rootCategoryId);
        $tree = CacheCompressor::compress($tree, $build);
        $tree->tag([self::CACHE_KEY]);
        $this->cache->save($tree);

        return $build;
    }

    /**
     * @param string $rootCategoryId - provide category id to build from
     * @return array - ['categoryId' => ['productId' => sortNumber]]
     */
    private function build(string $rootCategoryId): array
    {
        $tree = [];
        $categories = $this->categoryBridge->getChildCategories($rootCategoryId);
        foreach ($categories as $category) {
            $request = new Request();
            $request->setSession(new Session()); // 3rd party subscriber support

            if ($orderKey = $this->getSortOrderKey($category)) {
                $request->request->set('order', $orderKey);
            }
            $criteria = new Criteria();
            $criteria->setTitle('shopgate::product::category-id');
            $result = $this->listingRoute
                ->load($category->getId(), $request, $this->contextManager->getSalesContext(), $criteria)
                ->getResult();
            $products = $result->getEntities();
            $maxProducts = $products->count();
            $i = 0;
            /** @var ProductEntity $product */
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
