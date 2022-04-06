<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Events;

use ShopgateCustomer;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterRegisterCustomerEvent extends Event
{
    private SalesChannelContext $context;
    private ShopgateCustomer $shopgateCustomer;
    private CustomerEntity $shopwareCustomer;

    public function __construct(
        CustomerEntity $shopwareCustomer,
        ShopgateCustomer $shopgateCustomer,
        SalesChannelContext $context
    ) {
        $this->context = $context;
        $this->shopgateCustomer = $shopgateCustomer;
        $this->shopwareCustomer = $shopwareCustomer;
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
