<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Events;

use ShopgateCustomer;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeRegisterCustomerEvent extends Event
{
    private ShopgateCustomer $customer;

    /**
     * @param ShopgateCustomer $customer
     */
    public function __construct(ShopgateCustomer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return ShopgateCustomer
     */
    public function getCustomer(): ShopgateCustomer
    {
        return $this->customer;
    }
}
