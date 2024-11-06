<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Category;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\System\Log\FileLogger;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductMapBridge
{
    private const PAGE_LIMIT = 2000;

    public function __construct(
        private readonly Connection $db,
        private readonly SortTree $sortTree,
        private readonly FileLogger $logger
    ) {
    }

    /**
     * More performant DB writer, but will throw on duplicates
     * @throws Exception
     */
    public function createMappings(CategoryEntity $category, array $channel): int
    {
        $writeCount = 0;
        $page = 1;
        do {
            // this is the heaviest part of all of the code
            $result = $this->sortTree->getPaginatedCategoryProducts($category, $page++, self::PAGE_LIMIT);
            $pageCount = ceil($result->getTotal() / self::PAGE_LIMIT);
            $products = $result->getEntities();

            $batch = $products->map(
                function (ProductEntity $product) use ($category, $channel, $result) {
                    static $position = 0;
                    $this->logger->logDetails('Writing entry', [
                        'prod' => $product->getParentId() ?: $product->getId(),
                        'category_id' => $category->getId(),
                        'channel_id' => Uuid::fromBytesToHex($channel['sales_channel_id']),
                        'language_id' => Uuid::fromBytesToHex($channel['language_id']),
                        'sort_order' => $result->getTotal() - $position,
                    ]);
                    return [
                        'product_id' => Uuid::fromHexToBytes($product->getParentId() ?: $product->getId()),
                        'category_id' => Uuid::fromHexToBytes($category->getId()),
                        'channel_id' => $channel['sales_channel_id'],
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

    /**
     * This one is less safe as it will throw if duplicates exist
     * @throws Exception
     */
    private function insertBatch(array $batch): int
    {
        if (empty($batch)) {
            return 0;
        }
        $sql = 'INSERT INTO shopgate_go_category_product_mapping (product_id, category_id, sales_channel_id, language_id, product_version_id, category_version_id, sort_order) VALUES ';
        $params = [];

        foreach ($batch as $row) {
            $sql .= '(?, ?, ?, ?, ?, ?, ?), ';
            $params = array_merge($params, array_values($row));
        }
        $sql = rtrim($sql, ', ');
        $update = new RetryableQuery(
            $this->db,
            $this->db->prepare($sql)
        );

        return $update->execute($params);
    }

    /**
     * Less performant writer, but can handle duplicates
     * @throws Exception
     */
    public function upsertMappings(CategoryEntity $category, mixed $channel): int
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
        do {
            $result = $this->sortTree->getPaginatedCategoryProducts($category, $page++, self::PAGE_LIMIT);
            $pageCount = ceil($result->getTotal() / self::PAGE_LIMIT);
            $products = $result->getEntities();
            foreach ($products as $product) {
                $this->logger->logDetails('Writing entry', [
                    'prod' => $product->getParentId() ?: $product->getId(),
                    'category_id' => $category->getId(),
                    'channel_id' => Uuid::fromBytesToHex($channel['sales_channel_id']),
                    'language_id' => Uuid::fromBytesToHex($channel['language_id']),
                    'sort_order' => $result->getTotal() - $position,
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
        $channelIds = implode(',', array_map(fn($row) => $this->db->quote($row['sales_channel_id']), $channelEntries));
        $catIds = implode(',', array_map(fn($row) => $this->db->quote($row['id']), $categoryEntries));
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
        $langQuery = $languageId ? ' AND ct.language_id = :language_id ' : ' ';
        $langParams = $languageId ? ['language_id' => $languageId] : [];
        $langTypes = $languageId ? ['language_id' => ParameterType::BINARY] : [];

        return $this->db->fetchAllAssociative(
            'SELECT DISTINCT cat.id, cat.version_id, ct.name, ct.language_id, ct.slot_config
             FROM category as cat
             LEFT JOIN category_translation ct on cat.id = ct.category_id
             WHERE cat.id IN (:ids) AND cat.parent_id IS NOT NULL' .
            $langQuery . 'ORDER BY cat.auto_increment',
            ['ids' => Uuid::fromHexToBytesList($ids)] + $langParams,
            ['ids' => ArrayParameterType::BINARY] + $langTypes
        );
    }
}
