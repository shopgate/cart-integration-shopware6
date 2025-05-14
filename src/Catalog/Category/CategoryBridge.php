<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Category;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductCollection;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductEntity;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\SalesChannel\AbstractCategoryListRoute;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class CategoryBridge
{
    public function __construct(
        private readonly AbstractCategoryListRoute $categoryListRoute,
        private readonly ContextManager $contextManager,
        private readonly EntityRepository $categoryProductMapRepository,
        private readonly LoggerInterface $logger,
        private readonly Connection $db
    ) {
    }

    public function getRootCategoryId(): string
    {
        return $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId();
    }

    public function getChildCategories(string $parentId): CategoryCollection
    {
        $criteria = (new Criteria())
            ->addAssociation('media')
            ->addAssociation('seoUrls')
            ->addFilter(
                new ContainsFilter('path', '|' . $parentId . '|'),
                new RangeFilter('level', [
                    RangeFilter::GT => 1,
                    RangeFilter::LTE => 99,
                ])
            );
        $criteria->setTitle('shopgate::category::parent-id');
        $list = $this->categoryListRoute->load($criteria, $this->contextManager->getSalesContext())->getCategories();
        $tree = $this->buildTree($parentId, $list);

        return $this->flattenTree($tree, new CategoryCollection());
    }

    public function getCategoryProductMap(array $uids = []): CategoryProductCollection
    {
        // we are checking language because the sort order can be different per language
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('languageId', $this->contextManager->getSalesContext()->getLanguageId()));
        $entities = $this->getCategoryProductMapEntries($criteria, $uids);
        if ($entities->count() === 0) {
            // no language fallback because we are not always generating entries if sort order is same for different languages
            $entities = $this->getCategoryProductMapEntries(new Criteria(), $uids);
        }
        $entities->count() === 0 && $this->logger->debug(
            'No category/product mapping entities found in index. Run the indexer.'
        );

        return $entities;
    }

    /**
     * Maps dynamic category (stream) data into our IndexerMap format
     */
    public function getCategoryStreamMap(array $uids = []): CategoryProductCollection
    {
        $items = [];
        try {
            $items = $this->db->fetchAllAssociative(
                'SELECT ps.product_id, cat.id as category_id
             FROM product_stream_mapping as ps
             LEFT JOIN category cat on cat.product_stream_id = ps.product_stream_id
             WHERE ps.product_id IN (:ids) AND cat.parent_id IS NOT NULL ' .
                'ORDER BY ps.product_id',
                ['ids' => Uuid::fromHexToBytesList($uids)],
                ['ids' => ArrayParameterType::BINARY]
            );
        } catch (\Throwable $e) {
            $this->logger->error('Issue retrieving category stream map: ' . $e->getMessage());
        }

        $collection = [];
        foreach ($items as $item) {
            $prodId = Uuid::fromBytesToHex($item['product_id']);
            $catId = Uuid::fromBytesToHex($item['category_id']);
            $item = (new CategoryProductEntity())
                ->setProductId($prodId)
                ->setCategoryId($catId)
                ->setSortOrder(10);
            $item->setUniqueIdentifier($catId . '-' . $prodId);
            $collection[] = $item;
        }

        return new CategoryProductCollection($collection);
    }

    /**
     * Simply loads categories from products into our IndexerMap format
     */
    public function getCategoryProductMapFromProductList(ProductCollection $collection): CategoryProductCollection
    {
        $map = new CategoryProductCollection();
        $rootCategoryId = $this->contextManager->getSalesContext()->getSalesChannel()->getNavigationCategoryId();
        foreach ($collection as $product) {
            foreach ($product->getCategoryTree() ?? [] as $categoryId) {
                if ($categoryId === $rootCategoryId) {
                    continue;
                }
                $item = (new CategoryProductEntity())->setProductId($product->getId())
                    ->setCategoryId($categoryId)
                    ->setSortOrder(10);
                $item->setUniqueIdentifier($categoryId . '-' . $product->getId());
                $map->add($item);
            }
        }
        return $map;
    }

    private function buildTree(?string $parentId, CategoryCollection $categories): CategoryCollection
    {
        $children = new CategoryCollection();
        foreach ($categories as $key => $category) {
            if ($category->getParentId() !== $parentId) {
                continue;
            }
            $categories->remove($key);
            $children->add($category);
        }

        $children->sortByPosition();

        $items = new CategoryCollection();
        $maxChildren = $children->count();
        $i = 0;
        foreach ($children as $child) {
            $child->setChildren($this->buildTree($child->getId(), $categories));
            $child->setCustomFields(['sortOrder' => $maxChildren - $i++]);
            $items->add($child);
        }

        return $items;
    }

    private function flattenTree(CategoryCollection $list, CategoryCollection $result): CategoryCollection
    {
        foreach ($list as $item) {
            if ($item->getChildren()) {
                $result = $this->flattenTree($item->getChildren(), $result);
            }
            $result->add($item);
        }
        return $result;
    }

    private function getCategoryProductMapEntries(Criteria $criteria, array $uids = []): CategoryProductCollection
    {
        $channel = $this->contextManager->getSalesContext();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $channel->getSalesChannelId()));
        if ($uids) {
            $criteria->addFilter(new EqualsAnyFilter('productId', $uids));
        }

        return $this->categoryProductMapRepository->search($criteria, $channel->getContext())->getEntities();
    }
}
