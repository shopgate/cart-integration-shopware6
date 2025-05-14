<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\DataAbstractionLayer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Shopgate\Shopware\Catalog\Category\ProductMapBridge;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductIndexingMessage;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Log\FallbackLogger;
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
use Throwable;
use function count;
use function json_decode;

class CategoryProductMappingIndexer extends EntityIndexer
{
    public function __construct(
        private readonly Connection $db,
        private readonly IteratorFactory $iteratorFactory,
        private readonly ProductMapBridge $productMapBridge,
        private readonly EntityRepository $repository,
        private readonly ContextManager $contextManager,
        private readonly FallbackLogger $logger,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function getName(): string
    {
        return 'shopgate.go.category.product.mapping.indexer';
    }

    public function iterate(?array $offset): ?EntityIndexingMessage
    {
        // note that there is no sales channel at this point
        if ($this->systemConfigService->getBool(ConfigBridge::SYSTEM_CONFIG_IGNORE_SORT_ORDER)) {
            return null;
        }

        $iterator = $this->iteratorFactory->createIterator($this->repository->getDefinition(), $offset);
        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        $this->logger->logBasics('Running full index', ['category count' => count($ids)]);
        return new CategoryProductIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    /**
     * @throws Throwable
     */
    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        if ($this->systemConfigService->getBool(ConfigBridge::SYSTEM_CONFIG_IGNORE_SORT_ORDER)) {
            return null;
        }

        $ids = [];
        try {
            $categoryEvent = $event->getEventByEntityName(CategoryDefinition::ENTITY_NAME);
            if ($categoryEvent) {
                $ids = $this->handleCategoryEvent($categoryEvent);
            }

            $productCategoryEvent = $event->getEventByEntityName(ProductCategoryDefinition::ENTITY_NAME);
            if ($productCategoryEvent) {
                $ids = array_merge($ids, $this->handleCategoryEvent($productCategoryEvent));
            }
        } catch (Throwable $e) {
            $this->logger->writeThrowableEvent($e);
            throw $e;
        }

        if (empty($ids)) {
            return null;
        }

        $this->logger->logBasics('Running partial index', ['categories' => array_values($ids)]);
        return new CategoryProductIndexingMessage(array_values($ids), null, $event->getContext(), count($ids) > 20);
    }

    /**
     * @throws Throwable
     */
    public function handle(EntityIndexingMessage $message): void
    {
        // would love to avoid a try catch like this,
        // but no good way to intercept errors in SW6 async worker
        try {
            $this->handleMessage($message);
        } catch (Throwable $e) {
            $this->logger->writeThrowableEvent($e);
            throw $e;
        }
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function handleMessage(EntityIndexingMessage $message): void
    {
        $ids = array_unique(array_filter($message->getData()));
        if (empty($ids)) {
            return;
        }

        // sorted by default channel language first
        $channels = $this->db->fetchAllAssociative(
            'SELECT DISTINCT sg.sales_channel_id, sg.language_id, sc.language_id = sg.language_id AS defaultLang FROM shopgate_api_credentials as sg
                LEFT JOIN sales_channel as sc on sc.id = sg.sales_channel_id WHERE sg.active = 1 ORDER BY defaultLang DESC'
        );

        if (!$channels) {
            $this->logger->logBasics('No Shopgate interfaces exist, skipping index creation');
            return;
        }

        $delCount = 0;
        // makes sure to always delete when non-safe is on. Because the Performant will throw if same entries exist in DB
        $writeType = $this->systemConfigService->get(ConfigBridge::ADVANCED_CONFIG_INDEXER_WRITE_TYPE);
        $deleteType = $this->systemConfigService->get(ConfigBridge::ADVANCED_CONFIG_INDEXER_DELETE_TYPE);
        // php8.1 seems to be failing if this is not checked, not quite sure why
        $isFullIndex = property_exists($message, 'isFullIndexing') && $message->isFullIndexing;
        if ($writeType !== ConfigBridge::INDEXER_WRITE_TYPE_SAFE ||
            ($deleteType === null || $deleteType === ConfigBridge::INDEXER_DELETE_TYPE_ALWAYS) ||
            ($isFullIndex && $deleteType === ConfigBridge::INDEXER_DELETE_TYPE_FULL)) {
            $delCount = Profiler::trace(
                'shopgate:catalog:product:indexer:delete',
                function () use ($ids, $channels): int {
                    $this->logger->logBasics('Removing categories', ['categories' => array_values($ids)]);
                    return $this->productMapBridge->deleteCategories($ids, $channels);
                }
            );
        }

        $writeCount = Profiler::trace(
            'shopgate:catalog:product:indexer:update',
            function () use ($ids, $channels): int {
                return $this->insertMappings($ids, $channels);
            }
        );

        $msg = "Catalog/product map index table updated. Removed $delCount items. Written $writeCount items.";
        ($delCount || $writeCount) && $this->logger->writeEvent($msg);
        ($delCount || $writeCount) && $this->logger->logBasics($msg);
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
     */
    private function insertMappings(array $categoryIds, array $channelEntries): int
    {
        $writeCount = 0;
        // tracks sort order per language & skips generating duplicates
        $langSortOrder = [];
        $indexWriteType = $this->systemConfigService->get(ConfigBridge::ADVANCED_CONFIG_INDEXER_WRITE_TYPE);

        foreach ($channelEntries as $channel) {
            $channelId = Uuid::fromBytesToHex($channel['sales_channel_id']);
            $channelLangId = Uuid::fromBytesToHex($channel['language_id']);
            $salesChannelContext = $this->contextManager->createNewContext(
                $channelId,
                [SalesChannelContextService::LANGUAGE_ID => $channelLangId]
            );
            $this->contextManager->overwriteSalesContext($salesChannelContext);

            $defaultLang = $salesChannelContext->getSalesChannel()->getLanguageId();
            $categoryEntries = $this->productMapBridge->getCategoryList($categoryIds, $channelLangId);

            foreach ($categoryEntries as $rawCat) {
                $catId = Uuid::fromBytesToHex($rawCat['id']);
                $langId = Uuid::fromBytesToHex($rawCat['language_id']);
                // sort order is stored in slot_config, if it's the same across the languages, then skip mapping same sorts
                // our retriever will just pull the mappings using any language as fallback
                $findNotUnique = array_filter(
                    $langSortOrder,
                    function (array $category) use ($catId, $channelId, $rawCat, $langId, $defaultLang) {
                        $sameSlot = $category['slot'] === $rawCat['slot_config'] && $category['slot'] === null;
                        $sameChannel = $channelId === $category['channelId'];
                        return $category['catId'] === $catId && $sameSlot && $sameChannel;
                    }
                );
                if (!empty($findNotUnique)) {
                    $this->logger->logBasics(
                        'Skipping category mapping due to non-unique sort order',
                        [
                            'channel_id' => $channelId,
                            'channel_lang_id' => $langId,
                            'channel_default_lang_id' => $defaultLang,
                            'category_id' => $catId,
                            'slot' => $rawCat['slot_config']
                        ]
                    );
                    continue;
                }
                $category = new CategoryEntity();
                $category->setId($catId);
                $category->setVersionId(Uuid::fromBytesToHex($rawCat['version_id']));
                // mimic of SW6 behavior where a category with non-main channel language inherits sort order from main language
                $mainLangCategory = array_filter($langSortOrder, function ($category) use ($channelId, $defaultLang) {
                    return $category['langId'] === $defaultLang && $category['slot'] !== null && $channelId === $category['channelId'];
                });
                if ($defaultLang !== $langId && $rawCat['slot_config'] === null && !empty($mainLangCategory)) {
                    $this->logger->logBasics('Falling back to default language sort', [
                        'channel_id' => $channelId,
                        'channel_lang_id' => $langId,
                        'channel_default_lang_id' => $defaultLang,
                        'category_id' => $catId,
                        'slot' => $rawCat['slot_config']
                    ]);
                    $temp = array_pop($mainLangCategory);
                    $rawCat['slot_config'] = $temp['slot'];
                }
                $category->setSlotConfig($rawCat['slot_config'] ? json_decode($rawCat['slot_config'], true) : []);
                $category->setName($rawCat['name'] ?? null);
                $langSortOrder[] = [
                    'channelId' => $channelId,
                    'langId' => $langId,
                    'catId' => $catId,
                    'slot' => $rawCat['slot_config']
                ];

                if (!$indexWriteType || $indexWriteType === ConfigBridge::INDEXER_WRITE_TYPE_SAFE) {
                    $totalCreated = $this->productMapBridge->upsertMappings($category, $channel);
                } else {
                    $totalCreated = $this->productMapBridge->createMappings($category, $channel);
                }

                $this->logger->logBasics(
                    'Written index entities',
                    [
                        'channel_id' => $channelId,
                        'language_id' => $langId,
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
}
