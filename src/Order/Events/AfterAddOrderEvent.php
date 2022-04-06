<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use ShopgateOrder;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterAddOrderEvent extends Event
{
    private SalesChannelContext $context;
    private array $result;
    private ShopgateOrder $shopgateOrder;
    private OrderEntity $shopwareOrder;

    /**
     * @param SalesChannelContext $context
     * @param array $result
     * @param ShopgateOrder $shopgateOrder
     * @param OrderEntity $shopwareOrder
     */
    public function __construct(
        SalesChannelContext $context,
        array $result,
        ShopgateOrder $shopgateOrder,
        OrderEntity $shopwareOrder
    ) {
        $this->context = $context;
        $this->result = $result;
        $this->shopgateOrder = $shopgateOrder;
        $this->shopwareOrder = $shopwareOrder;
    }

    /**
     * @return SalesChannelContext
     */
    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function setResult(array $result): void
    {
        $this->result = $result;
    }

    /**
     * @return ShopgateOrder
     */
    public function getShopgateOrder(): ShopgateOrder
    {
        return $this->shopgateOrder;
    }

    /**
     * @return OrderEntity
     */
    public function getShopwareOrder(): OrderEntity
    {
        return $this->shopwareOrder;
    }
}
