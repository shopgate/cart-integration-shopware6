<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Customer;

use ShopgateCartBase;
use ShopgateCartCustomer;
use ShopgateCartCustomerGroup;
use ShopgateCustomer;
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

    /**
     * @param ShopgateCartBase $order
     * @return ShopgateCustomer
     */
    public function orderToShopgateCustomer(ShopgateCartBase $order): ShopgateCustomer
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
