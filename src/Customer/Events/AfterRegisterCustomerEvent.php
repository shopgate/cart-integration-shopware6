<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Events;

use ShopgateCustomer;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Symfony\Contracts\EventDispatcher\Event;

class AfterRegisterCustomerEvent extends Event
{
    private ShopgateCustomer $shopgateCustomer;
    private CustomerEntity $shopwareCustomer;

    /**
     * @param ShopgateCustomer $customer
     * @param CustomerEntity $customer
     */
    public function __construct(ShopgateCustomer $shopgateCustomer, CustomerEntity $shopwareCustomer)
    {
        $this->shopgateCustomer = $shopgateCustomer;
        $this->shopwareCustomer = $shopwareCustomer;
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
