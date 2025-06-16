<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Subscribers;

use Doctrine\DBAL\Exception;
use Shopgate\Shopware\Catalog\Category\ProductMapBridge;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductIndexingMessage;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Log\FallbackLogger;
use Shopware\Core\Content\Product\DataAbstractionLayer\ProductIndexer;
use Shopware\Core\Content\Product\Events\ProductIndexerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class ProductIndexerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ProductMapBridge $productMapBridge,
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
     * This is done as a listener instead of our indexer is that we must ensure
     * to run after the product.stream index. The queue process messes with that as
     * 1) the stream update gets added to queue 2) We add ours based on stream products
     * Since we are basing ours on a delayed stream update, there is nothing for us to queue
     *
     * @throws Throwable
     * @throws Exception
     */
    public function runProductStreamCategoryMapper(ProductIndexerEvent $event): void
    {
        if ($this->systemConfigService->getBool(ConfigBridge::ADVANCED_CONFIG_INDEXER_STREAM_UPDATES)) {
            return;
        }

        if (in_array(ProductIndexer::STREAM_UPDATER, $event->getSkip(), true)) {
            return;
        }

        $result = $this->productMapBridge->getProductStreamCategoryIds($event->getIds());
        if (!$result) {
            return;
        }

        $categoryIds = array_map(fn(array $row) => Uuid::fromBytesToHex($row['id']), $result);

        $message = new CategoryProductIndexingMessage(array_values($categoryIds), null, $event->getContext());
        $this->logger->logBasics('Running product stream listener', ['categories' => array_values($categoryIds)]);
        $this->indexer->handle($message);
    }
}
