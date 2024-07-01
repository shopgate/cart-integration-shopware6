<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\DataAbstractionLayer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Monolog\Level;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductIndexingMessage;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;

use function count;
use function json_decode;

class CategoryProductMappingIndexer extends EntityIndexer
{

    public function __construct(
        private readonly Connection $db,
        private readonly IteratorFactory $iteratorFactory,
        private readonly EntityRepository $repository,
        private readonly SortTree $sortTree,
        private readonly ContextManager $contextManager
    ) {
    }

    public function getName(): string
    {
        return 'shopgate.go.category.product.mapping.indexer';
    }

    public function iterate(?array $offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator($this->repository->getDefinition(), $offset);
        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new CategoryProductIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $ids = [];
        $categoryEvent = $event->getEventByEntityName(CategoryDefinition::ENTITY_NAME);
        if ($categoryEvent) {
            $ids = $this->handleCategoryEvent($categoryEvent);
        }

        $productCategoryEvent = $event->getEventByEntityName(ProductCategoryDefinition::ENTITY_NAME);
        if ($productCategoryEvent) {
            $ids = array_merge($ids, $this->handleCategoryEvent($productCategoryEvent));
        }

        if (empty($ids)) {
            return null;
        }

        return new CategoryProductIndexingMessage(array_values($ids), null, $event->getContext(), count($ids) > 20);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function handle(EntityIndexingMessage $message): void
    {
        $ids = array_unique(array_filter($message->getData()));
        if (empty($ids)) {
            return;
        }

        $channels = $this->db->fetchAllAssociative('SELECT DISTINCT sales_channel_id FROM shopgate_api_credentials');
        if (!$channels) {
            $this->writeLog('No Shopgate interfaces exist, skipping index creation', Level::Notice);
            return;
        }

        $categoryList = $this->db->fetchAllAssociative(
            'SELECT DISTINCT cat.id, cat.version_id, ct.slot_config
             FROM category as cat
             LEFT JOIN category_translation ct on cat.id = ct.category_id
             WHERE cat.id IN (:ids) AND cat.parent_id IS NOT NULL
             ORDER BY cat.auto_increment',
            ['ids' => Uuid::fromHexToBytesList($ids)],
            ['ids' => ArrayParameterType::BINARY]
        );

        $delCount = $this->deleteCategories($categoryList, $channels);
        $writeCount = $this->upsertCategories($categoryList, $channels);

        ($delCount || $writeCount) && $this->writeLog(
            "Catalog/product map index table updated. Removed $delCount items. Written $writeCount items."
        );
    }

    public function getTotal(): int
    {
        return $this->iteratorFactory->createIterator($this->repository->getDefinition())->fetchCount();
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(static::class);
    }

    /**
     * @throws Exception
     */
    private function deleteCategories(array $categoryEntries, array $channelEntries): int
    {
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
     * @throws JsonException
     * @throws Exception
     */
    private function upsertCategories(array $categoryEntries, array $channelEntries): int
    {
        $update = new RetryableQuery(
            $this->db,
            $this->db->prepare(
                'INSERT INTO shopgate_go_category_product_mapping (product_id, category_id, sales_channel_id, product_version_id, category_version_id, sort_order)
                    VALUES (:productId, :categoryId, :channelId, :productVersionId, :categoryVersionId, :sortOrder)
                    ON DUPLICATE KEY UPDATE product_id = :productId, category_id = :categoryId, sales_channel_id = :channelId,
                                            sort_order = :sortOrder, category_version_id = :categoryVersionId,
                                            product_version_id = :productVersionId'
            )
        );

        $writeCount = 0;
        foreach ($channelEntries as $channel) {
            $channelId = Uuid::fromBytesToHex($channel['sales_channel_id']);
            $salesChannelContext = $this->contextManager->createNewContext($channelId);
            $this->contextManager->overwriteSalesContext($salesChannelContext);

            foreach ($categoryEntries as $rawCat) {
                $category = new CategoryEntity();
                $category->setId(Uuid::fromBytesToHex($rawCat['id']));
                $category->setVersionId(Uuid::fromBytesToHex($rawCat['version_id']));
                $category->setSlotConfig($rawCat['slot_config'] ? json_decode($rawCat['slot_config'], true) : []);
                $products = $this->sortTree->getAllCategoryProducts($category);
                $maxProducts = $products->count();
                $i = 0;
                foreach ($products as $product) {
                    $writeCount += $update->execute([
                        'productId' => Uuid::fromHexToBytes($product->getParentId() ?: $product->getId()),
                        'categoryId' => Uuid::fromHexToBytes($category->getId()),
                        'channelId' => Uuid::fromHexToBytes($channelId),
                        'productVersionId' => Uuid::fromHexToBytes($product->getVersionId()),
                        'categoryVersionId' => Uuid::fromHexToBytes($category->getVersionId()),
                        'sortOrder' => $maxProducts - $i++,
                    ]);
                }
            }
        }

        return $writeCount;
    }

    /**
     * @throws Exception
     */
    private function writeLog(string $message, Level $level = Level::Info): void
    {
        $this->db->executeStatement(
            'INSERT INTO `log_entry` (`id`, `message`, `level`, `channel`, `created_at`) VALUES (:id, :message, :level, :channel, now())',
            [
                'id' => Uuid::randomBytes(),
                'message' => 'Shopgate Go: ' . $message,
                'level' => $level->value,
                'channel' => 'Shopgate Go',
            ]
        );
    }

    private function handleCategoryEvent(EntityWrittenEvent $categoryEvent): array
    {
        $ids = [];
        foreach ($categoryEvent->getWriteResults() as $write) {
            $primary = $write->getPrimaryKey()['categoryId'] ?? $write->getPrimaryKey();
            $ids[$primary] = $primary;
            // no need to generate for a category delete (DB cascade will handle)
            $isCategoryDelete = $write->getEntityName() === CategoryDefinition::ENTITY_NAME
                && $write->getOperation() === 'delete';
            // if no entity exist, don't process (nothing to update or delete)
            if (!$write->getExistence() || $isCategoryDelete) {
                unset($ids[$primary]);
            }
        }

        return $ids;
    }
}
