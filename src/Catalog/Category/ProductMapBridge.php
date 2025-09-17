<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Category;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\System\Db\DatabaseUtilityTrait;
use Shopgate\Shopware\System\Log\FallbackLogger;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductMapBridge
{
    use DatabaseUtilityTrait;

    private const PAGE_LIMIT = 25;

    public function __construct(
        private readonly Connection $db,
        private readonly SortTree $sortTree,
        private readonly FallbackLogger $logger
    ) {
    }

    /**
     * More performant DB writer, but will throw on duplicates
     */
    public function createMappings(CategoryEntity $category, array $channel): int
    {
        $writeCount = 0;
        $page = 1;
        $channelId = Uuid::fromBytesToHex($channel['sales_channel_id']);
        $channelLangId = Uuid::fromBytesToHex($channel['language_id']);
        do {
            // this is the heaviest part of all of the code
            $result = $this->sortTree->getPaginatedCategoryProducts($category, $page++, self::PAGE_LIMIT);
            $pageCount = ceil($result->getTotal() / self::PAGE_LIMIT);
            $products = $result->getEntities();
            $batch = $products->map(
                function (ProductEntity $product) use ($category, $channel, $channelId, $channelLangId, $result) {
                    static $position = 0;
                    $this->logger->logDetails('Writing entry', [
                        'product_id' => $product->getParentId() ?: $product->getId(),
                        'category_id' => $category->getId(),
                        'sales_channel_id' => $channelId,
                        'language_id' => $channelLangId,
                        'sort_order' => $result->getTotal() - $position,
                        'category_name' => $category->getName(),
                        'product_name' => $product->getTranslation('name'),
                    ]);
                    return [
                        'product_id' => Uuid::fromHexToBytes($product->getParentId() ?: $product->getId()),
                        'category_id' => Uuid::fromHexToBytes($category->getId()),
                        'sales_channel_id' => $channel['sales_channel_id'],
                        'language_id' => $channel['language_id'],
                        'product_version_id' => Uuid::fromHexToBytes($product->getVersionId()),
                        'category_version_id' => Uuid::fromHexToBytes($category->getVersionId()),
                        'sort_order' => $result->getTotal() - $position++,
                    ];
                }
            );
            $writeCount += $this->insertBatch($batch);
            unset($batch);
        } while ($page <= $pageCount);

        return $writeCount;
    }

    private function insertBatch(array $batch): int
    {
        if (empty($batch)) {
            return 0;
        }

        $queue = new MultiInsertQueryQueue($this->db, 250, true);
        foreach ($batch as $row) {
            $queue->addInsert('shopgate_go_category_product_mapping', $row);
        }
        $queue->execute();
        // unfortunately the count is not returned
        return count($batch);
    }

    /**
     * Less performant writer, but can handle duplicates
     * @throws Exception
     */
    public function upsertMappings(CategoryEntity $category, array $channel): int
    {
        $update = new RetryableQuery(
            $this->db,
            $this->db->prepare(
                'INSERT INTO shopgate_go_category_product_mapping (product_id, category_id, sort_order, sales_channel_id, language_id, product_version_id, category_version_id)
                    VALUES (:productId, :categoryId, :sortOrder, :channelId, :languageId, :productVersionId, :categoryVersionId)
                    ON DUPLICATE KEY UPDATE product_id = :productId, category_id = :categoryId, sales_channel_id = :channelId,
                                            language_id = :languageId, product_version_id = :productVersionId,
                                            sort_order = :sortOrder, category_version_id = :categoryVersionId'
            )
        );
        $writeCount = 0;
        $page = 1;
        $position = 0;
        $channelId = Uuid::fromBytesToHex($channel['sales_channel_id']);
        $channelLangId = Uuid::fromBytesToHex($channel['language_id']);
        do {
            $result = $this->sortTree->getPaginatedCategoryProducts($category, $page++, self::PAGE_LIMIT);
            $pageCount = ceil($result->getTotal() / self::PAGE_LIMIT);
            $products = $result->getEntities();
            foreach ($products as $product) {
                $this->logger->logDetails('Writing entry', [
                    'product_id' => $product->getParentId() ?: $product->getId(),
                    'category_id' => $category->getId(),
                    'channel_id' => $channelId,
                    'language_id' => $channelLangId,
                    'sort_order' => $result->getTotal() - $position,
                    'category_name' => $category->getName(),
                    'product_name' => $product->getTranslation('name'),
                ]);
                $writeCount += $update->execute([
                    'productId' => Uuid::fromHexToBytes($product->getParentId() ?: $product->getId()),
                    'categoryId' => Uuid::fromHexToBytes($category->getId()),
                    'channelId' => $channel['sales_channel_id'],
                    'languageId' => $channel['language_id'],
                    'productVersionId' => Uuid::fromHexToBytes($product->getVersionId()),
                    'categoryVersionId' => Uuid::fromHexToBytes($category->getVersionId()),
                    'sortOrder' => $result->getTotal() - $position++,
                ]);
            }
        } while ($page <= $pageCount);

        return $writeCount;
    }

    /**
     * We delete all the products in a category because
     * the sort order changes if a product gets added
     *
     * @throws Exception
     */
    public function deleteCategories(array $categoryIds, array $channelEntries): int
    {
        $categoryEntries = $this->getCategoryList($categoryIds);
        // delete category event could trigger product updates
        if (empty($categoryEntries)) {
            return 0;
        }
        $channelIds = implode(
            ',',
            array_unique(array_map(fn($row) => $this->db->quote($row['sales_channel_id']), $channelEntries))
        );
        $catIds = implode(',', array_unique(array_map(fn($row) => $this->db->quote($row['id']), $categoryEntries)));
        $delete = new RetryableQuery(
            $this->db,
            $this->db->prepare(
                "DELETE FROM shopgate_go_category_product_mapping
                        WHERE category_id IN ($catIds) AND sales_channel_id IN ($channelIds)"
            )
        );

        return $delete->execute();
    }

    /**
     * @param string[] $ids
     * @param ?string $languageId - optionally constraint by language
     *
     * @return array{id: string, version_id: string, name: string, slot_config: object}[]
     * @throws Exception
     */
    public function getCategoryList(array $ids, string $languageId = null): array
    {
        $query = $this->db->createQueryBuilder();
        $query->select('cat.id', 'cat.version_id', 'ct.name', 'ct.language_id', 'ct.slot_config');
        $query->from('category', 'cat');
        $query->leftJoin(
            'cat',
            'category_translation',
            'ct',
            'cat.id = ct.category_id AND cat.version_id = ct.category_version_id'
        );
        $query->andWhere('cat.id IN (:ids)');
        $query->andWhere('cat.version_id = :live');
        $query->andWhere('cat.parent_id IS NOT NULL');
        $query->orderBy('cat.auto_increment');

        if ($languageId) {
            $query->andWhere('ct.language_id = :language_id');
            $query->setParameter('language_id', Uuid::fromHexToBytes($languageId), ParameterType::BINARY);
        }

        $query->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);
        $query->setParameter('ids', Uuid::fromHexToBytesList($ids), $this->getBinaryParameterType());

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Based on products provided we return a list of categories
     * which include these products in streams. Original intent
     * is to re-index these categories
     * @throws Exception
     */
    public function getProductStreamCategoryIds(array $productIds): array
    {
        $query = $this->db->createQueryBuilder();
        $query->select('cat.id');
        $query->from('category', 'cat');
        $query->leftJoin(
            'cat',
            'product_stream_mapping',
            'ps',
            'ps.product_stream_id = cat.product_stream_id'
        );
        $query->andWhere('ps.product_id IN (:ids)');
        $query->setParameter('ids', Uuid::fromHexToBytesList($productIds), $this->getBinaryParameterType());

        return $query->executeQuery()->fetchAllAssociative();
    }
}
