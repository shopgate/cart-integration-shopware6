<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Storefront\Events\ContextChangedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RoundingOverwriteSubscriber implements EventSubscriberInterface
{

    public function __construct(private readonly RequestStack $request)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [ContextChangedEvent::class => ['rewriteRounding', 15]];
    }

    /**
     * We force rewrite to 3 to make SG App happier.
     * Should not do this on `add_order` to stick with SW rounding config
     */
    public function rewriteRounding(ContextChangedEvent $event): void
    {
        $request = $this->request->getCurrentRequest();
        if (!$request) {
            return;
        }
        $action = $request->request->get('action');
        if ($action !== 'check_cart') {
            return;
        }
        $event->getContext()->setItemRounding(new CashRoundingConfig(3, 0.01, true));
    }
}
