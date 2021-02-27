<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Document;

use Shopware\Core\Checkout\Document\Event\DocumentOrderCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentSubscriber implements EventSubscriberInterface
{
    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DocumentOrderCriteriaEvent::class => 'addShopgateOrderToCriteria'
        ];
    }

    /**
     * @param DocumentOrderCriteriaEvent $event
     * @noinspection PhpUnused
     */
    public function addShopgateOrderToCriteria(DocumentOrderCriteriaEvent $event): void
    {
        $event->getCriteria()->addAssociation('shopgateOrder');
    }
}
