<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Review\Subscriber;

use Shopgate\Shopware\Catalog\Review\Events\BeforeReviewLoadEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddChannelFilterSubscriber implements EventSubscriberInterface
{

    public function __construct(private readonly bool $exportAllChannelReviews)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [BeforeReviewLoadEvent::class => ['addChannelFilter', 20]];
    }

    public function addChannelFilter(BeforeReviewLoadEvent $event): void
    {
        if ($this->exportAllChannelReviews) {
            return;
        }
        $event->getCriteria()->addFilter(new EqualsFilter('salesChannelId', $event->getContext()->getSalesChannelId()));
    }
}
