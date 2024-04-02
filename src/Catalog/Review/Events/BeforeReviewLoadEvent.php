<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Review\Events;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeReviewLoadEvent extends Event
{

    public function __construct(private readonly Criteria $criteria, readonly private SalesChannelContext $context)
    {
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
