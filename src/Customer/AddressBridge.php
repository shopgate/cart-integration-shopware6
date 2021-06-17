<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractUpsertAddressRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AddressBridge
{
    private AbstractUpsertAddressRoute $upsertAddressRoute;

    /**
     * @param AbstractUpsertAddressRoute $upsertAddressRoute
     */
    public function __construct(AbstractUpsertAddressRoute $upsertAddressRoute)
    {
        $this->upsertAddressRoute = $upsertAddressRoute;
    }

    /**
     * @param RequestDataBag $dataBag
     * @param SalesChannelContext $context
     * @param CustomerEntity $customer
     * @return CustomerAddressEntity
     */
    public function addAddress(
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        CustomerEntity $customer
    ): CustomerAddressEntity {
        return $this->upsertAddressRoute->upsert(null, $dataBag, $context, $customer)->getAddress();
    }
}
