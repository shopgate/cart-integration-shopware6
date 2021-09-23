<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\RequestPersist;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\Controller\MainController;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedCriteriaEvent;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
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

    /**
     * Added backwards compatibility as CheckoutOrderPlacedCriteriaEvent exists in SW 6.4.4.0.
     * Logically if event exists, 1. save SG order first 2. add the SG order to criteria
     * Otherwise just save order via CheckoutOrderPlacedEvent
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return array_merge([
            OrderRouteRequestEvent::class => 'alterCriteria',
            DocumentOrderCriteriaEvent::class => 'alterCriteria',
        ],
            class_exists(CheckoutOrderPlacedCriteriaEvent::class)
                ? [
                CheckoutOrderPlacedCriteriaEvent::class => [
                    ['alterCriteria', 0],
                    ['saveShopgateOrderBeforeCriteria', 1]
                ]
            ]
                : [CheckoutOrderPlacedEvent::class => 'saveShopgateOrder']
        );
    }

    /**
     * @param OrderRouteRequestEvent|DocumentOrderCriteriaEvent|CheckoutOrderPlacedCriteriaEvent|Event $event
     */
    public function alterCriteria(Event $event): void
    {
        if (method_exists($event, 'getCriteria')) {
            $event->getCriteria()->addAssociation('shopgateOrder');
        }
    }

    /**
     * After the cart is converted into an order we save the incoming
     * SG order to the database.
     *
     * @param CheckoutOrderPlacedEvent $event
     *
     * @deprecated 2.0.0
     */
    public function saveShopgateOrder(CheckoutOrderPlacedEvent $event): void
    {
        if (!defined(MainController::IS_SHOPGATE)) {
            return;
        }

        $swOrder = $event->getOrder();
        $channelId = $event->getSalesChannelId();
        $order = $this->requestPersist->getIncomingOrder();
        $this->shopgateOrderBridge->saveEntity(
            (new ShopgateOrderEntity())->mapQuote($swOrder->getId(), $channelId, $order),
            $event->getContext()
        );
    }

    /**
     * After the cart is converted into an order we save the incoming
     * SG order to the database.
     * Note! This should be saved before alterCriteria is fired.
     *
     * @param CheckoutOrderPlacedCriteriaEvent $event
     */
    public function saveShopgateOrderBeforeCriteria(CheckoutOrderPlacedCriteriaEvent $event): void
    {
        if (!defined(MainController::IS_SHOPGATE)) {
            return;
        }

        $ids = $event->getCriteria()->getIds();
        $id = array_pop($ids);
        $channelId = $event->getSalesChannelContext()->getSalesChannelId();
        $order = $this->requestPersist->getIncomingOrder();
        $this->shopgateOrderBridge->saveEntity(
            (new ShopgateOrderEntity())->mapQuote($id, $channelId, $order),
            $event->getSalesChannelContext()->getContext()
        );
    }
}
