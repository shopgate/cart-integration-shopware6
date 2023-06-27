<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ShopgateOrderBridge
{
    private EntityRepository $shopgateOrderRepository;

    public function __construct(EntityRepository $shopgateOrderRepository)
    {
        $this->shopgateOrderRepository = $shopgateOrderRepository;
    }

    public function getOrderByNumber(string $shopgateOrderNumber, Context $context): EntitySearchResult
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('shopgateOrderNumber', $shopgateOrderNumber))
            ->addAssociation('order');
        $criteria->setTitle('shopgate::shopgate-order::order-number');

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
