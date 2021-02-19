<?php

namespace Shopgate\Shopware\Order\Mapping;

use Shopgate\Shopware\Customer\Mapping\AddressMapping;
use ShopgateAddress;
use ShopgateCartCustomer;
use ShopgateCartCustomerGroup;
use ShopgateCustomer;
use ShopgateOrder;
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

    /**
     * @param ShopgateAddress $address
     * @param SalesChannelContext $context
     * @return string|null
     */
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

    /**
     * @param ShopgateOrder $order
     * @return ShopgateCustomer
     */
    public function orderToShopgateCustomer(ShopgateOrder $order): ShopgateCustomer
    {
        $customer = new ShopgateCustomer();
        $customer->setMail($order->getMail());
        $customer->setAddresses([$order->getDeliveryAddress(), $order->getInvoiceAddress()]);
        $customer->setGender($order->getInvoiceAddress()->getGender());
        $customer->setBirthday($order->getInvoiceAddress()->getBirthday());
        $customer->setFirstName($order->getInvoiceAddress()->getFirstName());
        $customer->setLastName($order->getInvoiceAddress()->getLastName());

        return $customer;
    }
}
