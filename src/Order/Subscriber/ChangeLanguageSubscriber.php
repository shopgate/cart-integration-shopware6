<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Order\Events\BeforeAddOrderEvent;
use Shopgate\Shopware\Order\Events\BeforeCheckCartEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ChangeLanguageSubscriber implements EventSubscriberInterface
{

    private string $languageId;
    private AbstractContextSwitchRoute $contextSwitchRoute;

    public function __construct(string $languageId, AbstractContextSwitchRoute $contextSwitchRoute)
    {
        $this->languageId = $languageId;
        $this->contextSwitchRoute = $contextSwitchRoute;
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
     *
     * @param BeforeCheckCartEvent|BeforeAddOrderEvent $event
     */
    public function switchLanguage(Event $event): void
    {
        if ($event->getContext()->getSalesChannel()->getLanguageId() === $this->languageId) {
            return;
        }

        $this->contextSwitchRoute->switchContext(
            new RequestDataBag([SalesChannelContextService::LANGUAGE_ID => $this->languageId]),
            $event->getContext()
        );
        $event->getContext()->getSalesChannel()->setLanguageId($this->languageId);
    }
}
