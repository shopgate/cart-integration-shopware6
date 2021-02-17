<?php

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ShopgateOrderBridge
{
    /** @var EntityRepositoryInterface */
    private $shopgateOrderRepository;

    /**
     * @param EntityRepositoryInterface $shopgateOrderRepository
     */
    public function __construct(EntityRepositoryInterface $shopgateOrderRepository)
    {
        $this->shopgateOrderRepository = $shopgateOrderRepository;
    }

    /**
     * @param string $shopgateOrderNumber
     * @param SalesChannelContext $channel
     * @return bool
     */
    public function orderExists(string $shopgateOrderNumber, SalesChannelContext $channel): bool
    {
        return $this->shopgateOrderRepository
                ->search((new Criteria())->addFilter(
                    new EqualsFilter('shopgateOrderNumber', $shopgateOrderNumber)
                ), $channel->getContext())
                ->count() > 0;
    }

    /**
     * @param ShopgateOrderEntity $orderEntity
     * @param SalesChannelContext $channel
     * @return EntityWrittenContainerEvent
     */
    public function saveEntity(
        ShopgateOrderEntity $orderEntity,
        SalesChannelContext $channel
    ): EntityWrittenContainerEvent {
        return $this->shopgateOrderRepository->upsert([$orderEntity->toArray()], $channel->getContext());
    }
}
