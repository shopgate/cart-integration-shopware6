<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use ShopgateOrder;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterAddOrderEvent extends Event
{
    public function __construct(private array $result, private readonly OrderEntity $shopwareOrder, private readonly ShopgateOrder $shopgateOrder, private readonly SalesChannelContext $context)
    {
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
