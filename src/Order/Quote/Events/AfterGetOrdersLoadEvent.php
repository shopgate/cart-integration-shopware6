<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote\Events;

use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterGetOrdersLoadEvent extends Event
{

    public function __construct(private readonly EntityCollection $result, private readonly SalesChannelContext $context)
    {
    }

    public function getResult(): EntityCollection|OrderCollection
    {
        return $this->result;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
