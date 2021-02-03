<?php

namespace Shopgate\Shopware\Catalog\Product\Sort;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Shopgate\Shopware\Catalog\Category\CategoryBridge;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\FileCache;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\Request;

class SortTree
{
    public const CACHE_KEY = 'shopgate.sort.tree';
    /** @var ContextManager */
    private $contextManager;
    /** @var CategoryBridge */
    private $categoryBridge;
    /** @var ProductListingRoute */
    private $listingRoute;
    /** @var FileCache */
    private $cache;

    /**
     * @param ContextManager $contextManager
     * @param CategoryBridge $categoryBridge
     * @param ProductListingRoute $listingRoute
     * @param FileCache $cacheObject
     */
    public function __construct(
        FileCache $cacheObject,
        ContextManager $contextManager,
        CategoryBridge $categoryBridge,
        ProductListingRoute $listingRoute
    ) {
        $this->cache = $cacheObject;
        $this->contextManager = $contextManager;
        $this->categoryBridge = $categoryBridge;
        $this->listingRoute = $listingRoute;
    }

    /**
     * @param string|null $rootCategoryId
     * @return array
     * @throws MissingContextException
     * @throws InvalidArgumentException
     */
    public function getSortTree(?string $rootCategoryId = null): array
    {
        /** @var CacheItemInterface $tree */
        $tree = $this->cache->getItem(self::CACHE_KEY);
        if (!$tree->isHit()) {
            $build = $this->build($rootCategoryId);
            $tree->set($build);
            $this->cache->save($tree);
        }
        return $tree->get();
    }

    /**
     * @param null|string $rootCategoryId - provide category id to build from
     * @return array - ['categoryId' => ['productId' => sortNumber]]
     * @throws MissingContextException
     */
    private function build(?string $rootCategoryId): array
    {
        $tree = [];
        if (null === $rootCategoryId) {
            $rootCategoryId = $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId();
        }
        $categories = $this->categoryBridge->getChildCategories($rootCategoryId);
        /** @var CategoryEntity $category */
        foreach ($categories as $category) {
            $request = new Request();
            $config = array_merge(...array_values($category->getSlotConfig()));
            if (isset($config['defaultSorting']['value'])) {
                $request->request->set('order', $config['defaultSorting']['value']);
            }
            $result = $this->listingRoute
                ->load($category->getId(), $request, $this->contextManager->getSalesContext(), new Criteria())
                ->getResult();
            $products = $result->getEntities();
            $maxProducts = $products->count();
            $i = 0;
            foreach ($products as $product) {
                $tree[$category->getId()][$product->getId()] = $maxProducts - $i++;
            }
        }

        return $tree;
    }
}
