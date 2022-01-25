<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote\Events;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeGetOrdersLoadEvent extends Event
{
    private Criteria $criteria;
    private Request $request;
    private SalesChannelContext $context;

    public function __construct(Criteria $criteria, Request $request, SalesChannelContext $context)
    {
        $this->criteria = $criteria;
        $this->request = $request;
        $this->context = $context;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
