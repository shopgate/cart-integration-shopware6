<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderCollection;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ShopgateOrderBridge
{

    public function __construct(private readonly EntityRepository $shopgateOrderRepository)
    {
    }

    public function getListByIds(array $ids, Context $context): ShopgateOrderCollection|EntityCollection
    {
        $criteria = (new Criteria())->addFilter(new EqualsAnyFilter('shopwareOrderId', $ids));
        $criteria->setTitle('shopgate::shopgate-orders::by-ids');

        return $this->shopgateOrderRepository->search($criteria, $context)->getEntities();
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
