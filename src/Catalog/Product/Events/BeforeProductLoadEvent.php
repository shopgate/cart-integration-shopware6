<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Events;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeProductLoadEvent extends Event
{
    private Criteria $criteria;
    private SalesChannelContext $context;

    /**
     * @param Criteria $criteria
     * @param SalesChannelContext $context
     */
    public function __construct(Criteria $criteria, SalesChannelContext $context)
    {
        $this->criteria = $criteria;
        $this->context = $context;
    }

    /**
     * @return Criteria
     */
    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    /**
     * @return SalesChannelContext
     */
    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
