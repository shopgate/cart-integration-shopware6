<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ShopgateOrderBridge
{
    private EntityRepositoryInterface $shopgateOrderRepository;

    public function __construct(EntityRepositoryInterface $shopgateOrderRepository)
    {
        $this->shopgateOrderRepository = $shopgateOrderRepository;
    }

    public function getOrderByNumber(string $shopgateOrderNumber, Context $context): EntitySearchResult
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('shopgateOrderNumber', $shopgateOrderNumber))
            ->addAssociation('order');

        return $this->shopgateOrderRepository->search($criteria, $context);
    }

    public function orderExists(string $shopgateOrderNumber, Context $context): bool
    {
        return $this->getOrderByNumber($shopgateOrderNumber, $context)->count() > 0;
    }

    public function saveEntity(ShopgateOrderEntity $orderEntity, Context $context): EntityWrittenContainerEvent
    {
        return $this->shopgateOrderRepository->upsert([$orderEntity->toArray()], $context);
    }
}
