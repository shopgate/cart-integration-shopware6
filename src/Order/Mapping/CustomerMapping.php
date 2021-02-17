<?php

namespace Shopgate\Shopware\Order\Mapping;

use Shopgate\Shopware\Customer\Mapping\AddressMapping;
use ShopgateAddress;
use ShopgateCartCustomer;
use ShopgateCartCustomerGroup;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerMapping
{
    /**
     * @var AddressMapping
     */
    private $addressMapping;

    public function __construct(AddressMapping $addressMapping)
    {
        $this->addressMapping = $addressMapping;
    }

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

    public function getSelectedAddressId(ShopgateAddress $address, SalesChannelContext $context): ?string
    {
        $addresses = $this->addressMapping->mapFromShopware($context->getCustomer());
        foreach ($addresses as $shopwareAddress) {
            if ($shopwareAddress->equals($address)) {
                return $shopwareAddress->getId();
            }
        }
        return null;
    }
}
