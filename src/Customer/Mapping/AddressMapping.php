<?php

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\Exceptions\MissingContextException;
use ShopgateAddress;
use ShopgateCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class AddressMapping
{
    /** @var LocationMapping */
    private $locationMapping;
    /** @var SalutationMapping */
    private $salutationMapping;

    /**
     * @param LocationMapping $locationMapping
     * @param SalutationMapping $salutationMapping
     */
    public function __construct(LocationMapping $locationMapping, SalutationMapping $salutationMapping)
    {
        $this->locationMapping = $locationMapping;
        $this->salutationMapping = $salutationMapping;
    }

    /**
     * @param ShopgateAddress $shopgateAddress
     * @return RequestDataBag
     * @throws MissingContextException
     */
    public function mapAddressData(ShopgateAddress $shopgateAddress): RequestDataBag
    {
        $address = [];
        $address['salutationId'] = $this->salutationMapping->getSalutationIdByGender($shopgateAddress->getGender());
        $address['firstName'] = $shopgateAddress->getFirstName();
        $address['lastName'] = $shopgateAddress->getLastName();
        $address['street'] = $shopgateAddress->getStreet1();
        $address['zipcode'] = $shopgateAddress->getZipcode();
        $address['city'] = $shopgateAddress->getCity();
        $address['countryId'] = $this->locationMapping->getCountryIdByIso($shopgateAddress->getCountry());
        $address['countryStateId'] = $this->locationMapping->getStateIdByIso($shopgateAddress->getState());

        return new RequestDataBag($address);
    }

    /**
     * @param ShopgateCustomer $customer
     * @return ShopgateAddress
     * @throws ShopgateLibraryException
     */
    public function getBillingAddress(ShopgateCustomer $customer): ShopgateAddress
    {
        $anyAddress = null;
        foreach ($customer->getAddresses() as $shopgateAddress) {
            if ($shopgateAddress->getIsInvoiceAddress()) {
                return $shopgateAddress;
            }
            $anyAddress = $shopgateAddress;
        }
        if ($anyAddress !== null) {
            return $anyAddress;
        }

        throw new ShopgateLibraryException(
            ShopgateLibraryException::PLUGIN_NO_ADDRESSES_FOUND,
            null,
            false,
            false
        );
    }

    /**
     * @param ShopgateCustomer $customer
     * @return false|ShopgateAddress
     */
    public function getShippingAddress(ShopgateCustomer $customer)
    {
        foreach ($customer->getAddresses() as $shopgateAddress) {
            if ($shopgateAddress->getIsDeliveryAddress()) {
                return $shopgateAddress;
            }
        }
        return false;
    }
    /**
     * @param CustomerEntity $customerEntity
     * @param CustomerAddressEntity $addressEntity
     * @return int
     */
    public function mapAddressType(CustomerEntity $customerEntity, CustomerAddressEntity $addressEntity): int
    {
        switch ($addressEntity->getId()) {
            case $customerEntity->getDefaultBillingAddressId():
                return ShopgateAddress::INVOICE;
            case $customerEntity->getDefaultShippingAddressId():
                return ShopgateAddress::DELIVERY;
            default:
                return ShopgateAddress::BOTH;
        }
    }

    /**
     * @param string $type
     * @param CustomerAddressEntity $shopwareAddress
     * @return ShopgateAddress
     */
    public function mapAddress(CustomerAddressEntity $shopwareAddress, string $type): ShopgateAddress
    {
        $shopgateAddress = new ShopgateAddress();
        $shopgateAddress->setAddressType($type);
        $shopgateAddress->setId($shopwareAddress->getId());
        $shopgateAddress->setFirstName($shopwareAddress->getFirstName());
        $shopgateAddress->setLastName($shopwareAddress->getLastName());
        $shopgateAddress->setPhone($shopwareAddress->getPhoneNumber());
        $shopgateAddress->setStreet1($shopwareAddress->getStreet());
        $street2 = $shopwareAddress->getAdditionalAddressLine1();
        if ($shopwareAddress->getAdditionalAddressLine2()) {
            $street2 .= $street2 ? "\n" : '';
            $street2 .= $shopwareAddress->getAdditionalAddressLine2();
        }
        $shopgateAddress->setStreet2($street2);
        $shopgateAddress->setCity($shopwareAddress->getCity());
        $shopgateAddress->setZipcode($shopwareAddress->getZipcode());
        if ($shopwareAddress->getCountryState()) {
            $shopgateAddress->setState($shopwareAddress->getCountryState()->getShortCode());
        }
        if ($shopwareAddress->getCountry()) {
            $shopgateAddress->setCountry($shopwareAddress->getCountry()->getIso());
        }
        if ($shopwareAddress->getCustomer()) {
            $shopgateAddress->setMail($shopwareAddress->getCustomer()->getEmail());
        }

        return $shopgateAddress;
    }

    /**
     * @param CustomerAddressCollection $collection
     * @return string|null
     */
    public function getWorkingPhone(CustomerAddressCollection $collection): ?string
    {
        foreach ($collection as $addressEntity) {
            if ($addressEntity->getPhoneNumber()) {
                return $addressEntity->getPhoneNumber();
            }
        }
        return null;
    }

    /**
     * @param CustomerEntity $customerEntity
     * @return ShopgateAddress[]
     */
    public function mapFromShopware(CustomerEntity $customerEntity): array
    {
        $shopgateAddresses = [];
        foreach ($customerEntity->getAddresses() as $shopwareAddress) {
            $type = $this->mapAddressType($customerEntity, $shopwareAddress);
            $shopgateAddresses[] = $this->mapAddress($shopwareAddress, $type);
        }
        return $shopgateAddresses;
    }
}
