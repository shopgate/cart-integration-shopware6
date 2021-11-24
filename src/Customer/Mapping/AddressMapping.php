<?php

declare(strict_types=1);

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
    private LocationMapping $locationMapping;
    private SalutationMapping $salutationMapping;

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
    public function mapToShopwareAddress(ShopgateAddress $shopgateAddress): RequestDataBag
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
        $address = array_merge(
            $address,
            $shopgateAddress->getCompany() ? ['company' => $shopgateAddress->getCompany()] : [],
            $shopgateAddress->getStreet2() ? ['additionalAddressLine1' => $shopgateAddress->getStreet2()] : [],
            $shopgateAddress->getPhone() ? ['phoneNumber' => $shopgateAddress->getPhone()] : [],
            $shopgateAddress->getMobile() ? ['phoneNumber' => $shopgateAddress->getMobile()] : []
        );

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
     * @param ShopgateAddress $address
     * @param CustomerEntity $customer
     * @return string|null
     */
    public function getSelectedAddressId(ShopgateAddress $address, CustomerEntity $customer): ?string
    {
        $addresses = $this->mapFromShopware($customer);
        foreach ($addresses as $shopwareAddress) {
            if ($shopwareAddress->equals($address)) {
                return $shopwareAddress->getId();
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

    /**
     * @param CustomerEntity $customerEntity
     * @param CustomerAddressEntity $addressEntity
     * @return int
     */
    public function mapAddressType(CustomerEntity $customerEntity, CustomerAddressEntity $addressEntity): int
    {
        $isBoth = false;
        if ($customerEntity->getDefaultBillingAddressId() === $customerEntity->getDefaultShippingAddressId()) {
            $isBoth = ShopgateAddress::BOTH;
        }

        switch ($addressEntity->getId()) {
            case $customerEntity->getDefaultBillingAddressId():
                return $isBoth ?: ShopgateAddress::INVOICE;
            case $customerEntity->getDefaultShippingAddressId():
                return $isBoth ?: ShopgateAddress::DELIVERY;
            default:
                return ShopgateAddress::BOTH;
        }
    }

    /**
     * @param int $type
     * @param CustomerAddressEntity $shopwareAddress
     * @return ShopgateAddress
     */
    public function mapAddress(CustomerAddressEntity $shopwareAddress, int $type): ShopgateAddress
    {
        $shopgateAddress = new ShopgateAddress();
        $shopgateAddress->setAddressType($type);
        $shopgateAddress->setId($shopwareAddress->getId());
        $shopgateAddress->setFirstName($shopwareAddress->getFirstName());
        $shopgateAddress->setLastName($shopwareAddress->getLastName());
        $shopgateAddress->setPhone($shopwareAddress->getPhoneNumber());
        $shopgateAddress->setCompany($shopwareAddress->getCompany());
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
        if ($shopwareAddress->getSalutation()) {
            $shopgateAddress->setGender($this->salutationMapping->toShopgateGender($shopwareAddress->getSalutation()));
        }

        return $shopgateAddress;
    }

}
