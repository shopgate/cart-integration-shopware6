<?php

namespace Shopgate\Shopware\Order\Mapping;

use ShopgateCartCustomer;
use ShopgateCartCustomerGroup;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerMapping
{

    /**
     * @param SalesChannelContext $context
     * @return ShopgateCartCustomer
     */
    public function mapCartCustomer(SalesChannelContext $context): ShopgateCartCustomer
    {
        $customerGroupId = $context->getCurrentCustomerGroup()->getId();
        $sgCustomerGroup = new ShopgateCartCustomerGroup();
        $sgCustomerGroup->setId($customerGroupId);

        $customer = new ShopgateCartCustomer();
        $customer->setCustomerGroups([$sgCustomerGroup]);

        return $customer;
    }
}
