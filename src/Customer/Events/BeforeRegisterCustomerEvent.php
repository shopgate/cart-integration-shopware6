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

    /**
     * @param SalesChannelContext $context
     * @param ShopgateCustomer $customer
     */
    public function __construct(SalesChannelContext $context, ShopgateCustomer $customer)
    {
        $this->context = $context;
        $this->customer = $customer;
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
    public function getCustomer(): ShopgateCustomer
    {
        return $this->customer;
    }
}
