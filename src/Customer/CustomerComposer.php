<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer;

use Shopgate\Shopware\Customer\Events\AfterGetCustomerEvent;
use Shopgate\Shopware\Customer\Events\AfterRegisterCustomerEvent;
use Shopgate\Shopware\Customer\Events\BeforeRegisterCustomerEvent;
use Shopgate\Shopware\Customer\Mapping\CustomerMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use ShopgateCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

class CustomerComposer
{

    public function __construct(
        private readonly ContextManager           $contextManager,
        private readonly AbstractRegisterRoute    $registerRoute,
        private readonly CustomerBridge           $customerBridge,
        private readonly CustomerMapping          $customerMapping,
        private readonly ConfigBridge             $configBridge,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
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
        $context = $this->contextManager->loadByCustomerToken($token->getToken())->getSalesContext();
        $customer = $context->getCustomer();
        if (null === $customer) {
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                'User logged in context missing');
        }
        $swCustomer = $this->customerBridge->getDetailedContextCustomer($context);
        $sgCustomer = $this->customerMapping->mapToShopgateEntity($swCustomer);

        $this->eventDispatcher->dispatch(new AfterGetCustomerEvent($swCustomer, $sgCustomer, $context));

        return $sgCustomer;
    }

    /**
     * Null password for guest customer
     *
     * @throws ShopgateLibraryException
     */
    public function registerCustomer(?string $password, ShopgateCustomer $customer): CustomerEntity
    {
        $context = $this->contextManager->getSalesContext();
        $this->eventDispatcher->dispatch(new BeforeRegisterCustomerEvent($customer, $context));

        $dataBag = $this->customerMapping->mapToShopwareEntity($customer, $password);
        $dataBag->set('storefrontUrl', $this->configBridge->getCustomerOptInConfirmUrl($context));
        $dataBag->set('acceptedDataProtection', true);

        try {
            $shopwareCustomer = $this->registerRoute->register($dataBag, $context, false)->getCustomer();
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

        $this->eventDispatcher->dispatch(new AfterRegisterCustomerEvent($shopwareCustomer, $customer, $context));

        return $shopwareCustomer;
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
