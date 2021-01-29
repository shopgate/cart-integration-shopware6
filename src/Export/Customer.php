<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateAddress;
use ShopgateCustomer;
use ShopgateCustomerGroup;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\BadCredentialsException;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundException;
use Shopware\Core\Checkout\Customer\Exception\InactiveCustomerException;
use Shopware\Core\Checkout\Customer\SalesChannel\CustomerRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\LoginRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Symfony\Component\HttpFoundation\Request;

class Customer
{
    /** @var ContextManager */
    private $contextManager;
    /** @var LoginRoute */
    private $loginRoute;
    /** @var RequestDataBag */
    private $dataBag;
    /** @var CustomerRoute */
    private $customerRoute;

    /**
     * @param ContextManager $contextManager
     * @param LoginRoute $loginRoute
     * @param RequestDataBag $dataBag
     * @param CustomerRoute $customerRoute
     */
    public function __construct(
        ContextManager $contextManager,
        LoginRoute $loginRoute,
        RequestDataBag $dataBag,
        CustomerRoute $customerRoute
    ) {
        $this->contextManager = $contextManager;
        $this->loginRoute = $loginRoute;
        $this->dataBag = $dataBag;
        $this->customerRoute = $customerRoute;
    }

    /**
     * @param string $user
     * @param string $password
     * @return ShopgateCustomer
     * @throws MissingContextException|ShopgateLibraryException
     */
    public function getCustomerData(string $user, string $password): ShopgateCustomer
    {
        $token = $this->authenticate($user, $password);
        if (null === $token) {
            throw new MissingContextException('User token not found');
        }
        $shopwareCustomer = $this->contextManager->loadByCustomerToken($token->getToken())->getCustomer();
        if (null === $shopwareCustomer) {
            throw new MissingContextException('User logged in context missing');
        }

        $shopgateCustomer = new ShopgateCustomer();
        $shopgateCustomer->setRegistrationDate(
            $shopwareCustomer->getCreatedAt() ? $shopwareCustomer->getCreatedAt()->format('Y-m-d') : null
        );
        $shopgateCustomer->setNewsletterSubscription((int)$shopwareCustomer->getNewsletter());
        $shopgateCustomer->setCustomerId($shopwareCustomer->getId());
        $shopgateCustomer->setCustomerNumber($shopwareCustomer->getCustomerNumber());
        $shopgateCustomer->setMail($shopwareCustomer->getEmail());
        $shopgateCustomer->setFirstName($shopwareCustomer->getFirstName());
        $shopgateCustomer->setLastName($shopwareCustomer->getLastName());
        $shopgateCustomer->setBirthday(
            $shopwareCustomer->getBirthday() ? $shopwareCustomer->getBirthday()->format('Y-m-d') : null
        );

        /**
         * Additional data
         */
        $additionalCustomer = $this->customerRoute->load(
            new Request(),
            $this->contextManager->getSalesContext(),
            (new Criteria())->setLimit(1)
                ->addAssociation('group')
                ->addAssociation('salutation')
                ->addAssociation('addresses')
                ->addAssociation('addresses.country')
                ->addAssociation('addresses.countryState')
        )->getCustomer();
        // Phone
        $shopgateCustomer->setPhone($shopwareCustomer->getDefaultShippingAddress()
            ? $shopwareCustomer->getDefaultShippingAddress()->getPhoneNumber()
            : $this->getWorkingPhone($additionalCustomer->getAddresses()));
        // Gender
        if ($salutation = $additionalCustomer->getSalutation()) {
            if ($salutation->getSalutationKey() === 'mr') {
                $shopgateCustomer->setGender(ShopgateCustomer::MALE);
            } elseif ($salutation->getSalutationKey() === 'mrs') {
                $shopgateCustomer->setGender(ShopgateCustomer::FEMALE);
            }
        }
        // Groups
        if ($additionalCustomer->getGroup()) {
            $grp = new ShopgateCustomerGroup();
            $grp->setName($additionalCustomer->getGroup()->getName());
            $grp->setId($additionalCustomer->getGroup()->getId());
            $shopgateCustomer->setCustomerGroups([$grp]);
        }
        // Addresses
        $shopgateAddresses = [];
        foreach ($additionalCustomer->getAddresses() as $shopwareAddress) {
            $type = $this->mapAddressType($additionalCustomer, $shopwareAddress);
            $shopgateAddresses[] = $this->mapAddress($shopwareAddress, $type);
        }
        $shopgateCustomer->setAddresses($shopgateAddresses);
        $shopgateCustomer->setTaxClassId('1');
        $shopgateCustomer->setTaxClassKey('default');

        return $shopgateCustomer;
    }

    /**
     * @param string $email
     * @param string $password
     * @return ContextTokenResponse
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    private function authenticate(string $email, string $password): ?ContextTokenResponse
    {
        $this->dataBag->add(['email' => $email, 'password' => $password]);
        $context = $this->contextManager->getSalesContext();
        try {
            return $this->loginRoute->login($this->dataBag, $context);
        } catch (BadCredentialsException $e) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD,
                null,
                false,
                false
            );
        } catch (CustomerNotFoundException $e) {
        } catch (InactiveCustomerException $e) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_CUSTOMER_ACCOUNT_NOT_CONFIRMED,
                null,
                false,
                false
            );
        }
        //todo-log
        throw new ShopgateLibraryException(
            ShopgateLibraryException::PLUGIN_CUSTOMER_UNKNOWN_ERROR,
            null,
            false,
            false
        );
    }

    /**
     * @param CustomerAddressCollection $collection
     * @return string|null
     */
    private function getWorkingPhone(CustomerAddressCollection $collection): ?string
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
     * @param CustomerAddressEntity $addressEntity
     * @return int
     */
    private function mapAddressType(CustomerEntity $customerEntity, CustomerAddressEntity $addressEntity): int
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
    private function mapAddress(CustomerAddressEntity $shopwareAddress, string $type): ShopgateAddress
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
}
