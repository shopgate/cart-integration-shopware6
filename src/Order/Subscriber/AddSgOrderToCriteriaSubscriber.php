<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopware\Core\Checkout\Document\Event\DocumentOrderCriteriaEvent;
use Shopware\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AddSgOrderToCriteriaSubscriber implements EventSubscriberInterface
{
    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderRouteRequestEvent::class => 'alterCriteria',
            DocumentOrderCriteriaEvent::class => 'alterCriteria'
        ];
    }

    /**
     * @param OrderRouteRequestEvent|DocumentOrderCriteriaEvent|Event $event
     */
    public function alterCriteria(Event $event): void
    {
        if (method_exists($event, 'getCriteria')){
            $event->getCriteria()->addAssociation('shopgateOrder');
        }
    }
}
