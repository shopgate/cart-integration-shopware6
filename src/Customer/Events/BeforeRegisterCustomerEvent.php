<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Events;

use ShopgateCustomer;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeRegisterCustomerEvent extends Event
{
    private SalesChannelContext $context;
    private ShopgateCustomer $customer;

    public function __construct(ShopgateCustomer $customer, SalesChannelContext $context)
    {
        $this->customer = $customer;
        $this->context = $context;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getCustomer(): ShopgateCustomer
    {
        return $this->customer;
    }
}
