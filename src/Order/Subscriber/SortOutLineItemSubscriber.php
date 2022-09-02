<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Order\LineItem\Events\BeforeOutLineItemMappingEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SortOutLineItemSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [BeforeOutLineItemMappingEvent::class => ['sortLineItems', 40]];
    }

    /**
     * This helps with cases where one Coupon can have shipping & cart discounts
     * It will apply 2 line items for the same coupon code, we'd like the 0 priced
     * promo item to be above all negative priced ones so that it gets overwritten
     * if necessary. We wouldn't want it the other way around.
     */
    public function sortLineItems(BeforeOutLineItemMappingEvent $event): void
    {
        $event->getCart()->getLineItems()->sort(function (LineItem $x, LineItem $y) {
            return $x->getPrice() && $y->getPrice() ? $y->getPrice()->getUnitPrice() <=> $x->getPrice()->getUnitPrice() : 1;
        });
    }
}
