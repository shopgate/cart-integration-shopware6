<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\UpsertAddressRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AddressBridge
{
    /** @var UpsertAddressRoute */
    private $upsertAddressRoute;

    /**
     * @param UpsertAddressRoute $upsertAddressRoute
     */
    public function __construct(UpsertAddressRoute $upsertAddressRoute)
    {
        $this->upsertAddressRoute = $upsertAddressRoute;
    }

    /**
     * @param RequestDataBag $dataBag
     * @param SalesChannelContext $context
     * @param CustomerEntity $customer
     * @return CustomerAddressEntity
     */
    public function addAddress(RequestDataBag $dataBag, SalesChannelContext $context, CustomerEntity $customer): CustomerAddressEntity
    {
        return $this->upsertAddressRoute->upsert(null, $dataBag, $context, $customer)->getAddress();
    }
}
