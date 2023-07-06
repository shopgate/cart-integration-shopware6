<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\Shopgate\RequestPersist;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\Controller\MainController;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedCriteriaEvent;
use Shopware\Core\Checkout\Document\Event\CreditNoteOrdersEvent;
use Shopware\Core\Checkout\Document\Event\DeliveryNoteOrdersEvent;
use Shopware\Core\Checkout\Document\Event\DocumentOrderEvent;
use Shopware\Core\Checkout\Document\Event\InvoiceOrdersEvent;
use Shopware\Core\Checkout\Document\Event\StornoOrdersEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Flow\Events\BeforeLoadStorableFlowDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddSgOrderToCriteriaSubscriber implements EventSubscriberInterface
{

    public function __construct(private readonly RequestPersist $requestPersist, private readonly ShopgateOrderBridge $shopgateOrderBridge)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'flow.storer.order.criteria.event' => ['alterCriteria', 30], // email template inject
            CheckoutOrderPlacedCriteriaEvent::class => ['saveShopgateOrderBeforeCriteria', 25],
            InvoiceOrdersEvent::class => ['addSgOrder', 40],
            CreditNoteOrdersEvent::class => ['addSgOrder', 40],
            DeliveryNoteOrdersEvent::class => ['addSgOrder', 40],
            StornoOrdersEvent::class => ['addSgOrder', 40],
        ];
    }

    public function alterCriteria(BeforeLoadStorableFlowDataEvent $event): void
    {
        $event->getCriteria()->addAssociation('shopgateOrder');
    }

    public function addSgOrder(DocumentOrderEvent $event): void
    {
        if (method_exists($event, 'getOrders')) {
            $sgList = $this->shopgateOrderBridge->getListByIds($event->getOrders()->getIds(), $event->getContext());
            array_map(function (OrderEntity $order) use ($sgList) {
                $item = $sgList->getBySwOrderId($order->getId());
                $item && $order->addExtension('shopgateOrder', $item);
            }, $event->getOrders()->getElements());
        }
    }

    /**
     * After the cart is converted into an order we save the incoming
     * SG order to the database.
     * Note! This should be saved before alterCriteria is fired.
     */
    public function saveShopgateOrderBeforeCriteria(CheckoutOrderPlacedCriteriaEvent $event): void
    {
        if (!defined(MainController::IS_SHOPGATE)) {
            return;
        }

        $ids = $event->getCriteria()->getIds();
        $id = array_pop($ids);
        $channelId = $event->getSalesChannelContext()->getSalesChannelId();
        $order = $this->requestPersist->getEntity();
        $this->shopgateOrderBridge->saveEntity(
            (new ShopgateOrderEntity())->mapQuote($id, $channelId, $order),
            $event->getSalesChannelContext()->getContext()
        );
    }
}
