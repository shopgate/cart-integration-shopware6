<?php

namespace Shopgate\Shopware\Order;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Order\OrderEntity;

class OrderBridge
{
    /** @var EntityRepositoryInterface */
    private $orderRepository;

    /**
     * @param EntityRepositoryInterface $orderRepository
     */
    public function __construct(EntityRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param string $id
     * @param SalesChannelContext $channel
     * @return OrderEntity|null
     */
    public function load(string $id, SalesChannelContext $channel): ?OrderEntity
    {
        return $this->orderRepository
                ->search((new Criteria())->addFilter(
                    new EqualsFilter('id', $id)
                ), $channel->getContext())
                ->first();
    }
}
