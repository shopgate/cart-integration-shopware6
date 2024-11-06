<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\DataAbstractionLayer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Monolog\Level;
use Shopgate\Shopware\Catalog\Category\ProductMapBridge;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductIndexingMessage;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Log\FileLogger;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Profiling\Profiler;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SystemConfig\SystemConfigService;

use function count;
use function json_decode;

class CategoryProductMappingIndexer extends EntityIndexer
{
    private int $writeCount = 0;

    public function __construct(
        private readonly Connection $db,
        private readonly IteratorFactory $iteratorFactory,
        private readonly ProductMapBridge $productMapBridge,
        private readonly EntityRepository $repository,
        private readonly ContextManager $contextManager,
        private readonly FileLogger $logger,
        private readonly SystemConfigService $systemConfigService
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

        $this->logger->logBasics('Running full index', ['category count' => count($ids)]);
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

        $this->logger->logBasics('Running partial index', ['categories' => array_values($ids)]);
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
            $this->logger->logBasics('Removing categories', ['categories' => array_values($ids)]);
            return $this->productMapBridge->deleteCategories($ids, $channels);
        });

        $writeCount = Profiler::trace(
            'shopgate:catalog:product:indexer:update',
            function () use ($ids, $channels): int {
                return $this->upsertCategories($ids, $channels);
            }
        );

        $msg = "Catalog/product map index table updated. Removed $delCount items. Written $writeCount items.";
        ($delCount || $this->writeCount) && $this->writeLog($msg);
        ($delCount || $this->writeCount) && $this->logger->logBasics($msg);
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
     * @throws JsonException
     * @throws Exception
     * @todo: not upsert anymore
     */
    private function upsertCategories(array $categoryIds, array $channelEntries): int
    {
        $writeCount = 0;
        $langSortOrder = [];

        foreach ($channelEntries as $channel) {
            $channelId = Uuid::fromBytesToHex($channel['sales_channel_id']);
            $salesChannelContext = $this->contextManager->createNewContext(
                $channelId,
                [SalesChannelContextService::LANGUAGE_ID => Uuid::fromBytesToHex($channel['language_id'])]
            );
            $this->contextManager->overwriteSalesContext($salesChannelContext);
            // todo: make sure lang constraint does what it needs to do here
            $categoryEntries = $this->productMapBridge->getCategoryList($categoryIds, $channel['language_id']);

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


                $indexType = $this->systemConfigService->get(ConfigBridge::ADVANCED_CONFIG_INDEXER_WRITE_TYPE);
                if (!$indexType || $indexType === ConfigBridge::INDEXER_WRITE_TYPE_SAFE) {
                    $totalCreated = $this->productMapBridge->upsertMappings($category, $channel);
                } else {
                    $totalCreated = $this->productMapBridge->createMappings($category, $channel);
                }

                $this->logger->logBasics(
                    'Written index entities',
                    [
                        'channel_id' => Uuid::fromBytesToHex($channel['sales_channel_id']),
                        'language_id' => Uuid::fromBytesToHex($channel['language_id']),
                        'category_id' => $category->getName() ?: $category->getId(),
                        'count' => $totalCreated
                    ]
                );
                $writeCount += $totalCreated;
            }
        }

        return $writeCount;
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
