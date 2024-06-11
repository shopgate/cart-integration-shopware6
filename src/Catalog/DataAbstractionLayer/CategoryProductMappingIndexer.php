<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\DataAbstractionLayer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
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
        private readonly Connection $connection,
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
        // todo: maybe table should get dropped before full re-index?
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
        $channels = $this->connection->fetchAllAssociative('SELECT DISTINCT sales_channel_id FROM shopgate_api_credentials');

        if (!$channels) {
            $this->writeLog('No Shopgate interfaces exist, skipping index creation');
            return;
        }

        $result = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT cat.id, cat.version_id, ct.slot_config
             FROM category as cat
             LEFT JOIN category_translation ct on cat.id = ct.category_id
             WHERE cat.id IN (:ids) AND cat.parent_id IS NOT NULL
             ORDER BY cat.auto_increment',
            ['ids' => Uuid::fromHexToBytesList($ids)],
            ['ids' => ArrayParameterType::BINARY]
        );

        // index table update
        $update = new RetryableQuery(
            $this->connection,
            $this->connection->prepare(
                'INSERT INTO shopgate_go_category_product_mapping (product_id, category_id, sales_channel_id, product_version_id, category_version_id, sort_order)
                    VALUES (:productId, :categoryId, :channelId, :productVersionId, :categoryVersionId, :sortOrder)
                    ON DUPLICATE KEY UPDATE product_id = :productId, category_id = :categoryId, sales_channel_id = :channelId,
                                            sort_order = :sortOrder, category_version_id = :categoryVersionId,
                                            product_version_id = :productVersionId')
        );
        $count = 0;
        $channelIds = array_map(fn($channel) => $channel['sales_channel_id'], $channels);
        $categories = array_map(fn($category) => $category['id'], $result);

        $channelIds = implode(',', array_map([$this->connection, 'quote'], $channelIds));
        $categories = implode(',', array_map([$this->connection, 'quote'], $categories));

        $sql = "DELETE FROM shopgate_go_category_product_mapping WHERE category_id IN ($categories) AND sales_channel_id IN ($channelIds)";

        $delete = new RetryableQuery(
            $this->connection,
            $this->connection->prepare($sql)
        );

        $count += $delete->execute();

        foreach ($channels as $channel) {
            $channelId = Uuid::fromBytesToHex($channel['sales_channel_id']);
            $salesChannelContext = $this->contextManager->createNewContext($channelId);
            $this->contextManager->overwriteSalesContext($salesChannelContext);

            foreach ($result as $rawCat) {
                $category = new CategoryEntity();
                $category->setId(Uuid::fromBytesToHex($rawCat['id']));
                $category->setVersionId(Uuid::fromBytesToHex($rawCat['version_id']));
                $category->setSlotConfig($rawCat['slot_config'] ? json_decode($rawCat['slot_config'], true) : []);
                $products = $this->sortTree->getAllCategoryProducts($category);
                $maxProducts = $products->count();
                $i = 0;
                foreach ($products as $product) {
                    $count += $update->execute([
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
        $count && $this->writeLog('Successfully updated catalog/product map index table');
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
    private function writeLog(string $message): void
    {
        $this->connection->executeStatement('INSERT INTO `log_entry` (`id`, `message`, `level`, `channel`, `created_at`) VALUES (:id, :message, :level, :channel, now())',
            [
                'id' => Uuid::randomBytes(),
                'message' => $message,
                'level' => 200,
                'channel' => 'Shopgate Go',
            ]);
    }

    private function handleCategoryEvent(EntityWrittenEvent $categoryEvent): array
    {
        $ids = [];
        foreach ($categoryEvent->getWriteResults() as $result) {
            $primary = $result->getPrimaryKey();
            $ids[] = $primary['categoryId'] ?? $primary;
            // if no entity exist, don't process (nothing to update or delete)
            // no need to generate for a category delete (DB cascade will handle)
            $isCategoryDelete = $result->getEntityName() === CategoryDefinition::ENTITY_NAME && $result->getOperation() === 'delete';
            if (!$result->getExistence() || $isCategoryDelete) {
                $key = array_search($result->getPrimaryKey(), $ids);
                unset($ids[$key]);
            }
        }

        return $ids;
    }
}
