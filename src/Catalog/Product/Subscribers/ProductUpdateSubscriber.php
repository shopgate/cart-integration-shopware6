<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Subscribers;

use Psr\Cache\InvalidArgumentException;
use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\System\FileCache;
use Shopware\Core\Content\Category\CategoryEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class ProductUpdateSubscriber implements EventSubscriberInterface
{
    /** @var FileCache */
    private $fileCache;

    /**
     * @param FileCache $fileCache
     */
    public function __construct(FileCache $fileCache)
    {
        $this->fileCache = $fileCache;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CategoryEvents::CATEGORY_WRITTEN_EVENT => 'onUpdated'
        ];
    }

    /**
     * When a category sort order gets updated, we want to clear sort cache
     *
     * @param EntityWrittenEvent $event
     * @noinspection PhpUnusedParameterInspection
     */
    public function onUpdated(EntityWrittenEvent $event): void
    {
        try {
            $this->fileCache->deleteItem(SortTree::CACHE_KEY);
        } catch (Throwable | InvalidArgumentException $e) {
        }
    }
}
