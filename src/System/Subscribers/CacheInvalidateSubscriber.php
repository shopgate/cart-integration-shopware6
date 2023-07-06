<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Subscribers;

use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Content\Category\CategoryEvents;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SystemConfig\Event\BeforeSystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class CacheInvalidateSubscriber implements EventSubscriberInterface
{
    private const SORT_ORDER_KEY = ConfigBridge::SYSTEM_CONFIG_DOMAIN . ConfigBridge::SYSTEM_CONFIG_IGNORE_SORT_ORDER;
    private CacheInvalidator $cacheInvalidator;
    private bool $oldSortOrderConfigValue;
    private SystemConfigService $configService;

    public function __construct(CacheInvalidator $cacheInvalidator, SystemConfigService $configService)
    {
        $this->cacheInvalidator = $cacheInvalidator;
        $this->configService = $configService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CategoryEvents::CATEGORY_WRITTEN_EVENT => 'onUpdated',
            BeforeSystemConfigChangedEvent::class => 'cacheOldConfigValue',
            SystemConfigChangedEvent::class => 'onSystemConfigChanged'
        ];
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

    public function cacheOldConfigValue(BeforeSystemConfigChangedEvent $event): void
    {
        if ($event->getKey() !== self::SORT_ORDER_KEY) {
            return;
        }
        $this->oldSortOrderConfigValue = $this->configService->getBool(
            self::SORT_ORDER_KEY,
            $event->getSalesChannelId()
        );
    }

    /**
     * Clears cache only if the value has changed.
     * Is not affect by Admin API sync call though
     */
    public function onSystemConfigChanged(SystemConfigChangedEvent $event): void
    {
        try {
            if ($event->getKey() === self::SORT_ORDER_KEY && $event->getValue() !== $this->oldSortOrderConfigValue) {
                $this->cacheInvalidator->invalidate([SortTree::CACHE_KEY]);
            }
        } catch (Throwable $e) {
        }
    }
}
