<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Subscribers;

use Doctrine\DBAL\Exception;
use Shopgate\Shopware\Catalog\Category\ProductMapBridge;
use Shopgate\Shopware\Catalog\DataAbstractionLayer\CategoryProductMappingIndexer;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductIndexingMessage;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Log\FallbackLogger;
use Shopware\Core\Content\Product\DataAbstractionLayer\ProductIndexer;
use Shopware\Core\Content\Product\Events\ProductIndexerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class ProductIndexerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ProductMapBridge $productMapBridge,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityIndexer $indexer,
        private readonly FallbackLogger $logger,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ProductIndexerEvent::class => ['runProductStreamCategoryMapper', 30]];
    }

    /**
     * This is done as a listener instead of inside our indexer's update(), because the categories
     * to rebuild depend on the product stream mapping, which is only written by the product indexer.
     * ProductIndexerEvent is dispatched after that mapping is up to date, so the affected categories
     * are known here.
     *
     * By default the rebuild is dispatched to the message queue (SG-385). Running it inline made every
     * product save in the Administration rebuild all of its stream categories within the request.
     * Shops without a message queue worker can switch back to the inline rebuild via `indexerUseQueue`.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function runProductStreamCategoryMapper(ProductIndexerEvent $event): void
    {
        if ($this->systemConfigService->getBool(ConfigBridge::ADVANCED_CONFIG_INDEXER_STREAM_UPDATES)
            || $this->systemConfigService->getBool(ConfigBridge::SYSTEM_CONFIG_IGNORE_SORT_ORDER)) {
            return;
        }

        if (in_array(ProductIndexer::STREAM_UPDATER, $event->getSkip(), true)) {
            return;
        }

        $result = $this->productMapBridge->getProductStreamCategoryIds($event->getIds());
        if (!$result) {
            return;
        }

        $categoryIds = array_values(array_map(fn(array $row) => Uuid::fromBytesToHex($row['id']), $result));

        if (!ConfigBridge::isIndexerQueueEnabled($this->systemConfigService)) {
            $this->logger->logBasics('Running product stream listener', ['categories' => $categoryIds]);
            $this->indexer->handle(new CategoryProductIndexingMessage($categoryIds, null, $event->getContext()));

            return;
        }

        $message = new CategoryProductIndexingMessage($categoryIds, null, $event->getContext(), forceQueue: true);
        $message->setIndexer(CategoryProductMappingIndexer::NAME);
        $this->logger->logBasics('Queuing product stream categories', ['categories' => $categoryIds]);
        $this->messageBus->dispatch($message);
    }
}
