<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer;

use Shopgate\Shopware\Customer\Mapping\CustomerMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use ShopgateCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Throwable;

class CustomerComposer
{
    private ContextManager $contextManager;
    private AbstractRegisterRoute $registerRoute;
    private CustomerBridge $customerBridge;
    private CustomerMapping $customerMapping;
    private ConfigBridge $configBridge;

    public function __construct(
        ContextManager $contextManager,
        AbstractRegisterRoute $registerRoute,
        CustomerBridge $customerBridge,
        CustomerMapping $customerMapping,
        ConfigBridge $configBridge
    ) {
        $this->contextManager = $contextManager;
        $this->registerRoute = $registerRoute;
        $this->customerBridge = $customerBridge;
        $this->customerMapping = $customerMapping;
        $this->configBridge = $configBridge;
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function getCustomer(string $user, string $password): ShopgateCustomer
    {
        $token = $this->customerBridge->authenticate($user, $password);
        if (null === $token) {
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, 'User token not found');
        }
        $shopwareCustomer = $this->contextManager->loadByCustomerToken($token->getToken())->getCustomer();
        if (null === $shopwareCustomer) {
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                'User logged in context missing');
        }
        $detailedCustomer = $this->customerBridge->getDetailedContextCustomer($this->contextManager->getSalesContext());

        return $this->customerMapping->mapToShopgateEntity($detailedCustomer);
    }

    /**
     * @param string|null $password - pass null for guest customer
     * @param ShopgateCustomer $customer
     * @return CustomerEntity
     * @throws ShopgateLibraryException
     */
    public function registerCustomer(?string $password, ShopgateCustomer $customer): CustomerEntity
    {
        $chanel = $this->contextManager->getSalesContext();
        $dataBag = $this->customerMapping->mapToShopwareEntity($customer, $password);
        $dataBag->set('storefrontUrl', $this->configBridge->getCustomerOptInConfirmUrl($chanel));
        $dataBag->set('acceptedDataProtection', true);
        try {
            return $this->registerRoute->register($dataBag, $chanel, false)->getCustomer();
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

    public function getCustomerGroups(): array
    {
        $defaultCustomerGroupId = $this->contextManager->getSalesContext()->getCurrentCustomerGroup()->getId();
        $customerGroups = $this->customerBridge->getGroups();

        $result = [];
        foreach ($customerGroups as $id => $customerGroup) {
            $result[] = [
                'name' => $customerGroup->getTranslation('name') ?: $customerGroup->getName(),
                'id' => $id,
                'is_default' => $id === $defaultCustomerGroupId ? '1' : '0',
                'customer_tax_class_key' => 'default',
            ];
        }
        return $result;
    }
}
