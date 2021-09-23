<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ShopgateOrderBridge
{
    private EntityRepositoryInterface $shopgateOrderRepository;

    public function __construct(EntityRepositoryInterface $shopgateOrderRepository)
    {
        $this->shopgateOrderRepository = $shopgateOrderRepository;
    }

    /**
     * @param string $shopgateOrderNumber
     * @param Context $context
     * @return bool
     */
    public function orderExists(string $shopgateOrderNumber, Context $context): bool
    {
        return $this->shopgateOrderRepository
                ->search((new Criteria())->addFilter(
                    new EqualsFilter('shopgateOrderNumber', $shopgateOrderNumber)
                ), $context)
                ->count() > 0;
    }

    /**
     * @param Context $context
     * @return ShopgateOrderEntity[]
     */
    public function getOrdersNotSynced(Context $context): array
    {
        return $this->shopgateOrderRepository
            ->search(
                (new Criteria())
                    ->addFilter(new EqualsFilter('isSent', 0))
                    ->addFilter(new EqualsFilter('isCancelled', 0))
                    ->addAssociation('order'),
                $context
            )->getElements();
    }

    /**
     * @param ShopgateOrderEntity $orderEntity
     * @param Context $context
     * @return EntityWrittenContainerEvent
     */
    public function saveEntity(ShopgateOrderEntity $orderEntity, Context $context): EntityWrittenContainerEvent
    {
        return $this->shopgateOrderRepository->upsert([$orderEntity->toArray()], $context);
    }
}
