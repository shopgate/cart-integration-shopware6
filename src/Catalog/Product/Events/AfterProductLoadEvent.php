<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Events;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterProductLoadEvent extends Event
{
    private ProductCollection $productCollection;
    private Criteria $criteria;
    private SalesChannelContext $context;

    public function __construct(ProductCollection $productCollection, Criteria $criteria, SalesChannelContext $context)
    {
        $this->productCollection = $productCollection;
        $this->criteria = $criteria;
        $this->context = $context;
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
