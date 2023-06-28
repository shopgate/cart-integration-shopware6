<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Order\Events\BeforeAddOrderEvent;
use Shopgate\Shopware\Order\Events\BeforeCheckCartEvent;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChangeLanguageSubscriber implements EventSubscriberInterface
{

    private string $languageId;
    private ContextManager $contextManager;

    public function __construct(string $languageId, ContextManager $contextManager)
    {
        $this->languageId = $languageId;
        $this->contextManager = $contextManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCheckCartEvent::class => ['switchLanguage', 63],
            BeforeAddOrderEvent::class => ['switchLanguage', 63]
        ];
    }

    /**
     * Sometimes a customer may have a different language selected in
     * a session. Here we rewrite their session with SG channel mapped
     * language
     */
    public function switchLanguage(BeforeCheckCartEvent|BeforeAddOrderEvent $event): void
    {
        if ($event->getContext()->getLanguageId() === $this->languageId) {
            return;
        }

        $this->contextManager->switchContext(
            new RequestDataBag([SalesChannelContextService::LANGUAGE_ID => $this->languageId]),
            $event->getContext()
        );
    }
}
