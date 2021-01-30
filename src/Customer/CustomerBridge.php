<?php

namespace Shopgate\Shopware\Customer;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\Exception\InactiveCustomerException;
use Shopware\Core\Checkout\Customer\SalesChannel\LoginRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class CustomerBridge
{
    /** @var EntityRepositoryInterface */
    private $customerGroupRepository;
    /** @var ContextManager */
    private $contextManager;
    /** @var LoginRoute */
    private $loginRoute;
    /** @var RequestDataBag */
    private $dataBag;

    /**
     * @param EntityRepositoryInterface $customerGroupRepository
     * @param ContextManager $contextManager
     * @param LoginRoute $loginRoute
     * @param RequestDataBag $dataBag
     */
    public function __construct(
        EntityRepositoryInterface $customerGroupRepository,
        ContextManager $contextManager,
        LoginRoute $loginRoute,
        RequestDataBag $dataBag
    ) {
        $this->customerGroupRepository = $customerGroupRepository;
        $this->contextManager = $contextManager;
        $this->loginRoute = $loginRoute;
        $this->dataBag = $dataBag;
    }

    /**
     * @return array[]
     * @throws MissingContextException
     */
    public function getCustomerGroups(): array
    {
        $defaultCustomerGroupId = $this->contextManager->getSalesContext()->getCurrentCustomerGroup()->getId();
        $customerGroups = $this->customerGroupRepository
            ->search(new Criteria(), $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();

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

    /**
     * @param string $email
     * @param string $password
     * @return ContextTokenResponse
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function authenticate(string $email, string $password): ?ContextTokenResponse
    {
        $this->dataBag->add(['email' => $email, 'password' => $password]);
        $context = $this->contextManager->getSalesContext();
        try {
            return $this->loginRoute->login($this->dataBag, $context);
        } catch (UnauthorizedHttpException $e) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD,
                null,
                false,
                false
            );
        } catch (InactiveCustomerException $e) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_CUSTOMER_ACCOUNT_NOT_CONFIRMED,
                null,
                false,
                false
            );
        } catch (Throwable $throwable) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_CUSTOMER_UNKNOWN_ERROR,
                null,
                false,
                false
            );
        }
    }
}
