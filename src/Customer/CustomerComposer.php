<?php

namespace Shopgate\Shopware\Customer;

use Shopgate\Shopware\Customer\Mapping\CustomerMapping;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\SalesChannel\CustomerResponse;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Throwable;

class CustomerComposer
{
    /** @var ContextManager */
    private $contextManager;
    /** @var RegisterRoute */
    private $registerRoute;
    /** @var CustomerBridge */
    private $customerBridge;
    /** @var CustomerMapping */
    private $customerMapping;

    /**
     * @param ContextManager $contextManager
     * @param RegisterRoute $registerRoute
     * @param CustomerBridge $customerBridge
     * @param CustomerMapping $customerMapping
     */
    public function __construct(
        ContextManager $contextManager,
        RegisterRoute $registerRoute,
        CustomerBridge $customerBridge,
        CustomerMapping $customerMapping
    ) {
        $this->contextManager = $contextManager;
        $this->registerRoute = $registerRoute;
        $this->customerBridge = $customerBridge;
        $this->customerMapping = $customerMapping;
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
        $detailedCustomer = $this->customerBridge->getDetailedContextCustomer();

        return $this->customerMapping->mapToShopgateEntity($detailedCustomer);
    }

    /**
     * @param string|null $password - pass null for guest customer
     * @param ShopgateCustomer $customer
     * @return CustomerResponse
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function registerCustomer(?string $password, ShopgateCustomer $customer): CustomerResponse
    {
        $dataBag = $this->customerMapping->mapToShopwareEntity($customer, $password);
        try {
            return $this->registerRoute->register($dataBag, $this->contextManager->getSalesContext(), false);
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
