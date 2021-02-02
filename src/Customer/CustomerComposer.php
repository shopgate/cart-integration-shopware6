<?php

namespace Shopgate\Shopware\Customer;

use Shopgate\Shopware\Customer\Mapping\AddressMapping;
use Shopgate\Shopware\Customer\Mapping\GroupMapping;
use Shopgate\Shopware\Customer\Mapping\SalutationMapping;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\SalesChannel\CustomerRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class CustomerComposer
{
    /** @var ContextManager */
    private $contextManager;
    /** @var CustomerRoute */
    private $customerRoute;
    /** @var RegisterRoute */
    private $registerRoute;
    /** @var AddressMapping */
    private $addressMapping;
    /** @var CustomerBridge */
    private $customerBridge;
    /** @var GroupMapping */
    private $groupMapping;
    /** @var SalutationMapping */
    private $salutationMapping;

    /**
     * @param ContextManager $contextManager
     * @param CustomerRoute $customerRoute
     * @param RegisterRoute $registerRoute
     * @param AddressMapping $addressMapping
     * @param CustomerBridge $customerBridge
     * @param GroupMapping $groupMapping
     * @param SalutationMapping $salutationMapping
     */
    public function __construct(
        ContextManager $contextManager,
        CustomerRoute $customerRoute,
        RegisterRoute $registerRoute,
        AddressMapping $addressMapping,
        CustomerBridge $customerBridge,
        GroupMapping $groupMapping,
        SalutationMapping $salutationMapping
    ) {
        $this->contextManager = $contextManager;
        $this->customerRoute = $customerRoute;
        $this->registerRoute = $registerRoute;
        $this->addressMapping = $addressMapping;
        $this->customerBridge = $customerBridge;
        $this->groupMapping = $groupMapping;
        $this->salutationMapping = $salutationMapping;
    }

    /**
     * @param string $user
     * @param string $password
     * @return ShopgateCustomer
     * @throws MissingContextException|ShopgateLibraryException
     */
    public function getCustomer(string $user, string $password): ShopgateCustomer
    {
        $token = $this->customerBridge->authenticate($user, $password);
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
            : $this->addressMapping->getWorkingPhone($additionalCustomer->getAddresses()));
        // Gender
        if ($salutation = $additionalCustomer->getSalutation()) {
            $shopgateCustomer->setGender($this->salutationMapping->toShopgateGender($salutation));
        }
        // Groups
        if ($group = $additionalCustomer->getGroup()) {
            $shopgateCustomer->setCustomerGroups([$this->groupMapping->toShopgateGroup($group)]);
        }
        // Addresses
        $shopgateCustomer->setAddresses($this->addressMapping->mapFromShopware($additionalCustomer));
        $shopgateCustomer->setTaxClassId('1');
        $shopgateCustomer->setTaxClassKey('default');

        return $shopgateCustomer;
    }

    /**
     * @param string $user
     * @param string $password
     * @param ShopgateCustomer $customer
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function registerCustomer(string $user, string $password, ShopgateCustomer $customer): void
    {
        $data = [];
        $data['email'] = $user;
        $data['password'] = $password;
        $data['salutationId'] = $this->salutationMapping->getSalutationIdByGender($customer->getGender());
        $data['firstName'] = $customer->getFirstName();
        $data['lastName'] = $customer->getLastName();
        $shopgateBillingAddress = $this->addressMapping->getBillingAddress($customer);
        $shopgateShippingAddress = $this->addressMapping->getShippingAddress($customer);
        $data['billingAddress'] = $this->addressMapping->mapAddressData($shopgateBillingAddress);
        if ($shopgateShippingAddress !== false) {
            $data['shippingAddress'] = $this->addressMapping->mapAddressData($shopgateShippingAddress);
        }
        $dataBag = new RequestDataBag($data);
        try {
            $this->registerRoute->register($dataBag, $this->contextManager->getSalesContext(), false);
        } catch (ConstraintViolationException $e) {
            $errorMessages = [];
            foreach ($e->getViolations() as $violation) {
                if ($violation->getCode() === 'VIOLATION::CUSTOMER_EMAIL_NOT_UNIQUE') {
                    throw new ShopgateLibraryException(
                        ShopgateLibraryException::REGISTER_USER_ALREADY_EXISTS,
                        $violation->getMessage(),
                        true
                    );
                }
                $errorMessages[] = 'violation: ' . $violation->getMessage() . ' path: ' . $violation->getPropertyPath();
            }
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                implode(' ', $errorMessages),
                true
            );
        } catch (Throwable $e) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                $e->getMessage(),
                true
            );
        }
    }

    /**
     * @return array
     * @throws MissingContextException
     */
    public function getCustomerGroups(): array
    {
        $defaultCustomerGroupId = $this->contextManager->getSalesContext()->getCurrentCustomerGroup()->getId();
        $customerGroups = $this->customerBridge->getGroups();

        $result = [];
        foreach ($customerGroups as $id => $customerGroup) {
            $result[] = [
                'name' => $customerGroup->getName(),
                'id' => $id,
                'is_default' => $id === $defaultCustomerGroupId ? '1' : '0',
                'customer_tax_class_key' => 'default',
            ];
        }
        return $result;
    }
}
