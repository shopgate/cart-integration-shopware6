<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\System\CustomFields\CustomFieldMapping;
use ShopgateCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class CustomerMapping
{

    public function __construct(
        private readonly GroupMapping      $groupMapping,
        private readonly AddressMapping    $addressMapping,
        private readonly SalutationMapping $salutationMapping,
        private readonly CustomFieldMapping $customFieldMapping
    ) {
    }

    public function mapToShopgateEntity(CustomerEntity $detailedCustomer): ShopgateCustomer
    {
        $shopgateCustomer = new ShopgateCustomer();
        $shopgateCustomer->setRegistrationDate($detailedCustomer->getCreatedAt()?->format('Y-m-d'));
        if (method_exists($detailedCustomer, 'getNewsletter')) {
            $shopgateCustomer->setNewsletterSubscription((int)$detailedCustomer->getNewsletter());
        }
        $shopgateCustomer->setCustomerId($detailedCustomer->getId());
        $shopgateCustomer->setCustomerToken($detailedCustomer->getId());
        $shopgateCustomer->setCustomerNumber($detailedCustomer->getCustomerNumber());
        $shopgateCustomer->setMail($detailedCustomer->getEmail());
        $shopgateCustomer->setFirstName($detailedCustomer->getFirstName());
        $shopgateCustomer->setLastName($detailedCustomer->getLastName());
        $shopgateCustomer->setBirthday($detailedCustomer->getBirthday()?->format('Y-m-d'));

        // Phone
        $shopgateCustomer->setPhone($detailedCustomer->getDefaultShippingAddress()
            ? $detailedCustomer->getDefaultShippingAddress()->getPhoneNumber()
            : $this->addressMapping->getWorkingPhone($detailedCustomer->getAddresses()));
        // Gender
        if ($salutation = $detailedCustomer->getSalutation()) {
            $shopgateCustomer->setGender($this->salutationMapping->toShopgateGender($salutation));
        }
        // Groups
        if ($group = $detailedCustomer->getGroup()) {
            $shopgateCustomer->setCustomerGroups([$this->groupMapping->toShopgateGroup($group)]);
        }
        // Custom Fields
        $customFields = $this->customFieldMapping->mapToShopgateCustomFields($detailedCustomer);
        $shopgateCustomer->setCustomFields($customFields);
        // Addresses
        $shopgateCustomer->setAddresses($this->addressMapping->mapFromShopware($detailedCustomer));
        $shopgateCustomer->setTaxClassId('1');
        $shopgateCustomer->setTaxClassKey('default');

        return $shopgateCustomer;
    }

    /**
     * @param ?string $password - set to null for guest
     * @throws ShopgateLibraryException
     */
    public function mapToShopwareEntity(ShopgateCustomer $customer, ?string $password): RequestDataBag
    {
        $data = [];
        $bd = [];
        $shopgateBillingAddress = $this->addressMapping->getBillingAddress($customer);
        $shopgateShippingAddress = $this->addressMapping->getShippingAddress($customer);
        if (!empty($customer->getBirthday())) {
            $bd = explode('-', $customer->getBirthday()); // yyyy-mm-dd
        }
        $isAddressIdentical = $this->addressMapping->areIdentical($shopgateBillingAddress, $shopgateShippingAddress);

        $data = array_merge(
            $data,
            $this->customFieldMapping->mapToShopwareCustomFields($customer),
            null === $password ? ['guest' => true] : ['password' => $password],
            $customer->getMail() ? ['email' => $customer->getMail()] : [],
            $customer->getGender()
                ? ['salutationId' => $this->salutationMapping->getSalutationIdByGender($customer->getGender())] : [],
            $customer->getFirstName() ? ['firstName' => $customer->getFirstName()] : [],
            $customer->getLastName() ? ['lastName' => $customer->getLastName()] : [],
            count($bd) === 3 ? ['birthdayYear' => $bd[0], 'birthdayMonth' => $bd[1], 'birthdayDay' => $bd[2]] : [],
            ['billingAddress' => $this->addressMapping->mapToShopwareAddress($shopgateBillingAddress)],
            $shopgateShippingAddress && !$isAddressIdentical
                ? ['shippingAddress' => $this->addressMapping->mapToShopwareAddress($shopgateShippingAddress)] : []
        );

        return new RequestDataBag($data);
    }
}
