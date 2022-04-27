<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Subscribers;

use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopware\Core\Content\Category\CategoryEvents;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class CacheInvalidateSubscriber implements EventSubscriberInterface
{
    private CacheInvalidator $cacheInvalidator;

    public function __construct(CacheInvalidator $cacheInvalidator)
    {
        $this->cacheInvalidator = $cacheInvalidator;
    }

    public static function getSubscribedEvents(): array
    {
        return [CategoryEvents::CATEGORY_WRITTEN_EVENT => 'onUpdated'];
    }

    /**
     * Product sort order can be edited on the category page
     *
     * @param EntityWrittenEvent $event
     * @noinspection PhpUnusedParameterInspection
     */
    public function onUpdated(EntityWrittenEvent $event): void
    {
        try {
            $this->cacheInvalidator->invalidate([SortTree::CACHE_KEY]);
        } catch (Throwable $e) {
        }
    }
}
