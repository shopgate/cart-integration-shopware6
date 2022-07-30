<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Events;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class ContextChangedEvent extends Event
{
    private SalesChannelContext $context;

    public function __construct(SalesChannelContext $context)
    {
        $this->context = $context;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
