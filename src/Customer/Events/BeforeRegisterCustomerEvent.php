<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Events;

use ShopgateCustomer;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeRegisterCustomerEvent extends Event
{
    public function __construct(private readonly ShopgateCustomer $customer, private readonly SalesChannelContext $context)
    {
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
