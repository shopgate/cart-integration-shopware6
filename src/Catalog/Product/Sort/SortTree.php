<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Sort;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\Listing\AbstractProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class SortTree
{
    public function __construct(
        private readonly ContextManager $contextManager,
        private readonly AbstractProductListingRoute $listingRoute,
        private readonly EntityRepository $productSortingRepository
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
        $channel = $this->contextManager->getSalesContext();
        $productSorts = $this->productSortingRepository->search(new Criteria(), $channel->getContext())->getEntities();

        do {
            $request = new Request();
            $request->setMethod(Request::METHOD_POST);
            $request->request->set('p', $page++);
            $request->request->set('limit', $limit);
            $request->setSession(new Session()); // 3rd party subscriber support
            if ($orderKey = $this->getSortOrderKey($category, $productSorts)) {
                $request->request->set('order', $orderKey);
            }
            $criteria = new Criteria();
            $criteria->setTitle('shopgate::product::category-id');
            $result = $this->listingRoute->load($category->getId(), $request, $channel, $criteria)->getResult();
            $list->merge($result->getEntities());
            $pageCount = ceil($result->getTotal() / $limit);
        } while ($page <= $pageCount);

        return $list;
    }

    /**
     * Retrieves the default key to sort the category by
     *
     * @param CategoryEntity $category
     * @param ProductSortingCollection $sortingCollection
     * @return string|null - e.g. price-asc, topseller
     */
    private function getSortOrderKey(CategoryEntity $category, ProductSortingCollection $sortingCollection): ?string
    {
        if ($slot = (array)$category->getSlotConfig()) {
            $list = array_values($slot);
            if (is_array($list[0])) {
                $config = array_merge(...$list);
                if (isset($config['defaultSorting']['value'])) {
                    $value = $config['defaultSorting']['value'];
                    // in SW6.6 this became a UUID
                    if (Uuid::isValid($value) && $entry = $sortingCollection->get($value)) {
                        return $entry->getKey();
                    }
                    return $value;
                }
            }
        }

        return null;
    }
}
