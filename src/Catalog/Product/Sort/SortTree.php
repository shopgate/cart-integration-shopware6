<?php

namespace Shopgate\Shopware\Catalog\Product\Sort;

use Shopgate\Shopware\Catalog\Category\CategoryBridge;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\Request;

class SortTree
{
    /** @var ContextManager */
    private $contextManager;
    /** @var CategoryBridge */
    private $categoryBridge;
    /** @var ProductListingRoute */
    private $listingRoute;
    /** @var array */
    private $sortTree;

    /**
     * @param ContextManager $contextManager
     * @param CategoryBridge $categoryBridge
     * @param ProductListingRoute $listingRoute
     */
    public function __construct(
        ContextManager $contextManager,
        CategoryBridge $categoryBridge,
        ProductListingRoute $listingRoute
    ) {
        $this->contextManager = $contextManager;
        $this->categoryBridge = $categoryBridge;
        $this->listingRoute = $listingRoute;
    }

    /**
     * @todo-konstantin: cache this
     * @param string|null $rootCategoryId
     * @return array
     * @throws MissingContextException
     */
    public function getSortTree(?string $rootCategoryId = null): array
    {
        if (null === $this->sortTree) {
            $this->sortTree = $this->build($rootCategoryId);
        }
        return $this->sortTree;
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
        //todo-konstantin: don't load no product categories
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
            /** @var ProductCollection $entities */
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
