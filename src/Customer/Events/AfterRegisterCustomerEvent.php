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

    /**
     * @param SalesChannelContext $context
     * @param ShopgateCustomer $shopgateCustomer
     * @param CustomerEntity $shopwareCustomer
     */
    public function __construct(SalesChannelContext $context, ShopgateCustomer $shopgateCustomer, CustomerEntity $shopwareCustomer)
    {
        $this->context = $context;
        $this->shopgateCustomer = $shopgateCustomer;
        $this->shopwareCustomer = $shopwareCustomer;
    }

    /**
     * @return SalesChannelContext
     */
    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    /**
     * @return ShopgateCustomer
     */
    public function getShopgateCustomer(): ShopgateCustomer
    {
        return $this->shopgateCustomer;
    }

    /**
     * @return CustomerEntity
     */
    public function getShopwareCustomer(): CustomerEntity
    {
        return $this->shopwareCustomer;
    }
}
