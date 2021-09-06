<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\RequestPersist;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\Controller\MainController;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedCriteriaEvent;
use Shopware\Core\Checkout\Document\Event\DocumentOrderCriteriaEvent;
use Shopware\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AddSgOrderToCriteriaSubscriber implements EventSubscriberInterface
{

    private RequestPersist $requestPersist;
    private ShopgateOrderBridge $shopgateOrderBridge;

    public function __construct(RequestPersist $requestPersist, ShopgateOrderBridge $shopgateOrderBridge)
    {
        $this->requestPersist = $requestPersist;
        $this->shopgateOrderBridge = $shopgateOrderBridge;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderRouteRequestEvent::class => 'alterCriteria',
            DocumentOrderCriteriaEvent::class => 'alterCriteria',
            CheckoutOrderPlacedCriteriaEvent::class => 'alterCriteria'
        ];
    }

    /**
     * @param OrderRouteRequestEvent|DocumentOrderCriteriaEvent|CheckoutOrderPlacedCriteriaEvent|Event $event
     */
    public function alterCriteria(Event $event): void
    {
        if (!defined(MainController::IS_SHOPGATE)) {
            return;
        }

        if ($event instanceof CheckoutOrderPlacedCriteriaEvent) {
            $this->saveShopgateOrder($event);
        }

        if (method_exists($event, 'getCriteria')) {
            $event->getCriteria()->addAssociation('shopgateOrder');
        }
    }

    /**
     * After the cart is converted into an order we save the incoming
     * SG order to the database.
     *
     * @param CheckoutOrderPlacedCriteriaEvent $event
     */
    public function saveShopgateOrder(CheckoutOrderPlacedCriteriaEvent $event): void
    {
        $ids = $event->getCriteria()->getIds();
        $id = array_pop($ids);
        $channelId = $event->getSalesChannelContext()->getSalesChannelId();
        $order = $this->requestPersist->getIncomingOrder();
        $this->shopgateOrderBridge->saveEntity(
            (new ShopgateOrderEntity())->mapQuote(
                $id,
                $channelId,
                $order
            ),
            $event->getSalesChannelContext()
        );
    }
}
