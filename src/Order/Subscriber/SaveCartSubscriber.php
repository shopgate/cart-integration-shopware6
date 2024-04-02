<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Storefront\Controller\MainController;
use Shopware\Core\Checkout\Cart\Event\AfterLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SaveCartSubscriber implements EventSubscriberInterface
{

    public function __construct(private readonly CartService $cartService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [AfterLineItemAddedEvent::class => ['cacheCartAfterItemAdd', 15]];
    }

    /**
     * Saving the cart after add items helps with 3rd party extensions listening
     * to this event & re-calculating before the cart is cached in the cartService
     */
    public function cacheCartAfterItemAdd(AfterLineItemAddedEvent $event): void
    {
        if (!defined(MainController::IS_SHOPGATE)) {
            return;
        }
        $this->cartService->setCart($event->getCart());
    }
}
