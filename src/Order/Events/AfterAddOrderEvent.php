<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use ShopgateOrder;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterAddOrderEvent extends Event
{
    private array $result;
    private ShopgateOrder $shopgateOrder;
    private OrderEntity $shopwareOrder;
    private SalesChannelContext $context;

    public function __construct(
        array $result,
        OrderEntity $shopwareOrder,
        ShopgateOrder $shopgateOrder,
        SalesChannelContext $context
    ) {
        $this->context = $context;
        $this->result = $result;
        $this->shopgateOrder = $shopgateOrder;
        $this->shopwareOrder = $shopwareOrder;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): void
    {
        $this->result = $result;
    }

    public function getShopgateOrder(): ShopgateOrder
    {
        return $this->shopgateOrder;
    }

    public function getShopwareOrder(): OrderEntity
    {
        return $this->shopwareOrder;
    }
}
