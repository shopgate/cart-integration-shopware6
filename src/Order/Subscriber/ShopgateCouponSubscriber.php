<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Order\LineItem\Events\AfterIncItemMappingEvent;
use ShopgateOrderItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShopgateCouponSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [AfterIncItemMappingEvent::class => ['removeIncomingCoupon', 20]];
    }

    /**
     * We do not support Shopgate coupons
     */
    public function removeIncomingCoupon(AfterIncItemMappingEvent $event): void
    {
        if ($event->getItem()->getType() !== ShopgateOrderItem::TYPE_SHOPGATE_COUPON) {
            return;
        }
        $event->getMapping()->set(AfterIncItemMappingEvent::SKIP, true);
    }
}
