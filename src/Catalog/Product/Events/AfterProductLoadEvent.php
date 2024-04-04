<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Events;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterProductLoadEvent extends Event
{

    public function __construct(
        private readonly ProductCollection $productCollection,
        private readonly Criteria $criteria,
        private readonly SalesChannelContext $context
    ) {
    }

    public function getProductCollection(): ProductCollection
    {
        return $this->productCollection;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getSaleChannelContext(): SalesChannelContext
    {
        return $this->context;
    }
}
