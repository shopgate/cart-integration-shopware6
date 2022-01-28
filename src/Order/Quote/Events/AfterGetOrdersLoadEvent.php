<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote\Events;

use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterGetOrdersLoadEvent extends Event
{
    private EntityCollection $result;
    private SalesChannelContext $context;

    public function __construct(EntityCollection $result, SalesChannelContext $context)
    {
        $this->result = $result;
        $this->context = $context;
    }

    /**
     * @return EntityCollection|OrderCollection
     */
    public function getResult(): EntityCollection
    {
        return $this->result;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
