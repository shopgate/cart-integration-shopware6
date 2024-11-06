<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\DataAbstractionLayer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use JsonException;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductIndexingMessage;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Profiling\Profiler;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;

use function count;
use function json_decode;

class CategoryProductMappingIndexer extends EntityIndexer
{
    private const PAGE_LIMIT = 2000;
    private string $sequence;
    private int $writeCount = 0;

    public function __construct(
        private readonly Connection $db,
        private readonly IteratorFactory $iteratorFactory,
        private readonly EntityRepository $repository,
        private readonly SortTree $sortTree,
        private readonly ContextManager $contextManager,
        private readonly LoggerInterface $logger
    ) {
        $this->sequence = Uuid::randomHex();
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

        $this->logBasics('Running full index', ['category count' => count($ids)]);
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

        $this->logBasics('Running partial index', ['categories' => array_values($ids)]);
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

        $channels = $this->db->fetchAllAssociative(
            'SELECT DISTINCT sales_channel_id, language_id FROM shopgate_api_credentials WHERE active = 1'
        );
        if (!$channels) {
            $this->writeLog('No Shopgate interfaces exist, skipping index creation', Level::Notice);
            return;
        }

        $delCount = Profiler::trace('shopgate:catalog:product:indexer:delete', function () use ($ids, $channels): int {
            return $this->deleteCategories($ids, $channels);
        });

        Profiler::trace(
            'shopgate:catalog:product:indexer:update',
            function () use ($ids, $channels) {
                $this->upsertCategories($ids, $channels);
            }
        );

        $msg = "Catalog/product map index table updated. Removed $delCount items. Written $this->writeCount items.";
        ($delCount || $this->writeCount) && $this->writeLog($msg);
        ($delCount || $this->writeCount) && $this->logBasics($msg);
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
     * We delete all the products in a category because
     * the sort order changes if a product gets added
     *
     * @throws Exception
     */
    private function deleteCategories(array $categoryIds, array $channelEntries): int
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
        $this->logBasics('Removing categories', $categoryIds);

        return $delete->execute();
    }

    /**
     * @throws JsonException
     * @throws Exception
     * @todo: not upsert anymore
     */
    private function upsertCategories(array $categoryIds, array $channelEntries): void
    {
        $langSortOrder = [];

        foreach ($channelEntries as $channel) {
            $channelId = Uuid::fromBytesToHex($channel['sales_channel_id']);
            $salesChannelContext = $this->contextManager->createNewContext(
                $channelId,
                [SalesChannelContextService::LANGUAGE_ID => Uuid::fromBytesToHex($channel['language_id'])]
            );
            $this->contextManager->overwriteSalesContext($salesChannelContext);
            // todo: make sure lang constraint does what it needs to do here
            $categoryEntries = $this->getCategoryList($categoryIds, $channel['language_id']);

            foreach ($categoryEntries as $rawCat) {
                // todo: check, can we get away with mapping one set if slot_configs are null on the same language?
                //  but then, we will need to check for this somehow when searching the mappings (with fallback)
                $catId = Uuid::fromBytesToHex($rawCat['id']);
                // sort order is stored in slot_config, if it's the same across the languages, then skip mapping
                // our retriever will just pull the mappings using any language as fallback
                $findNotUnique = array_filter($langSortOrder, function (array $category) use ($catId, $rawCat) {
                    return $category['catId'] === $catId && $category['slot'] === $rawCat['slot_config'];
                });
                if (!empty($findNotUnique)) {
                    continue;
                }
                $category = new CategoryEntity();
                $category->setId($catId);
                $category->setVersionId(Uuid::fromBytesToHex($rawCat['version_id']));
                $category->setSlotConfig($rawCat['slot_config'] ? json_decode($rawCat['slot_config'], true) : []);
                $category->setName($rawCat['name'] ?? null);
                $langSortOrder[] = ['catId' => $catId, 'slot' => $rawCat['slot_config']];


                // todo: config needed
                 $totalCreated = $this->createMappings($category, $channel);
//                $totalCreated = $this->upsertMappings($category, $channel);

                $this->logBasics(
                    'Written index entities',
                    [
                        'sequence' => $this->sequence,
                        'channel_id' => Uuid::fromBytesToHex($channel['sales_channel_id']),
                        'language_id' => Uuid::fromBytesToHex($channel['language_id']),
                        'category_id' => $category->getName() ?: $category->getId(),
                        'count' => $totalCreated
                    ]
                );
            }
        }
    }

    /**
     * Parses events & returns category ids that need to be processed
     */
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

    /**
     * @param string[] $ids
     * @param ?string $languageId - optionally constraint by language
     *
     * @return array{id: string, version_id: string, name: string, slot_config: object}[]
     * @throws Exception
     */
    private function getCategoryList(array $ids, string $languageId = null): array
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

    /**
     * More performant DB writer, but will throw on duplicates
     * @throws Exception
     */
    private function createMappings(CategoryEntity $category, array $channel): int
    {
        $page = 1;
        do {
            // this is the heaviest part of all of the code
            $result = $this->sortTree->getPaginatedCategoryProducts($category, $page++, self::PAGE_LIMIT);
            $pageCount = ceil($result->getTotal() / self::PAGE_LIMIT);
            $products = $result->getEntities();

            $batch = $products->map(
                function (ProductEntity $product) use ($category, $channel, $result) {
                    static $position = 0;
                    $this->logDetails('Writing entry', [
                        'sequence' => $this->sequence,
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
            $this->writeCount += $this->insertBatch($batch);
            unset($batch);
        } while ($page <= $pageCount);

        return $result->getTotal();
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
    private function upsertMappings(CategoryEntity $category, mixed $channel): int
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
        $page = 1;
        $position = 0;
        do {
            $result = $this->sortTree->getPaginatedCategoryProducts($category, $page++, self::PAGE_LIMIT);
            $pageCount = ceil($result->getTotal() / self::PAGE_LIMIT);
            $products = $result->getEntities();
            foreach ($products as $product) {
                $this->logDetails('Writing entry', [
                    'sequence' => $this->sequence,
                    'prod' => $product->getParentId() ?: $product->getId(),
                    'category_id' => $category->getId(),
                    'channel_id' => Uuid::fromBytesToHex($channel['sales_channel_id']),
                    'language_id' => Uuid::fromBytesToHex($channel['language_id']),
                    'sort_order' => $result->getTotal() - $position,
                ]);
                $this->writeCount += $update->execute([
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

        return $result->getTotal();
    }

    private function logDetails(string $message, array $context = []): void
    {
        // todo: check config to see if logging should be done
//        $this->logToFile($message, $context);
    }

    private function logBasics(string $message, array $context = []): void
    {
        // todo: check config to see if logging should be done
        $this->logToFile($message, $context);
    }

    /**
     * File storage logging for details
     */
    private function logToFile(string $message, array $context = []): void
    {
        if (count($context) > 50) {
            $context = ['truncated' => true] + array_slice($context, 50);
        }
        $this->logger->debug($message, $context);
    }

    /**
     * @throws Exception
     */
    private function writeLog(string $message, Level $level = Level::Info): void
    {
        // todo: create and check config for logging
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
}
