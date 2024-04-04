<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Events;

use ShopgateCustomer;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterRegisterCustomerEvent extends Event
{
    public function __construct(
        private readonly CustomerEntity $shopwareCustomer,
        private readonly ShopgateCustomer $shopgateCustomer,
        private readonly SalesChannelContext $context
    ) {
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getShopgateCustomer(): ShopgateCustomer
    {
        return $this->shopgateCustomer;
    }

    public function getShopwareCustomer(): CustomerEntity
    {
        return $this->shopwareCustomer;
    }
}
