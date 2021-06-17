<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\InactiveCustomerException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractCustomerRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractLoginRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class CustomerBridge
{
    private EntityRepositoryInterface $customerGroupRepository;
    private ContextManager $contextManager;
    private AbstractLoginRoute $loginRoute;
    private RequestDataBag $dataBag;
    private AbstractCustomerRoute $customerRoute;

    /**
     * @param EntityRepositoryInterface $customerGroupRepository
     * @param ContextManager $contextManager
     * @param AbstractLoginRoute $loginRoute
     * @param RequestDataBag $dataBag
     * @param AbstractCustomerRoute $customerRoute
     */
    public function __construct(
        EntityRepositoryInterface $customerGroupRepository,
        ContextManager $contextManager,
        AbstractLoginRoute $loginRoute,
        RequestDataBag $dataBag,
        AbstractCustomerRoute $customerRoute
    ) {
        $this->customerGroupRepository = $customerGroupRepository;
        $this->contextManager = $contextManager;
        $this->loginRoute = $loginRoute;
        $this->dataBag = $dataBag;
        $this->customerRoute = $customerRoute;
    }

    /**
     * @return CustomerGroupCollection|EntityCollection
     * @throws MissingContextException
     */
    public function getGroups()
    {
        return $this->customerGroupRepository
            ->search(new Criteria(), $this->contextManager->getSalesContext()->getContext())
            ->getEntities();
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

    /**
     * @param SalesChannelContext $context
     * @return CustomerEntity
     */
    public function getDetailedContextCustomer(SalesChannelContext $context): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setId($context->getCustomer() ? $context->getCustomer()->getId() : null);
        return $this->customerRoute->load(
            new Request(),
            $context,
            (new Criteria())->setLimit(1)
                ->addAssociation('group')
                ->addAssociation('salutation')
                ->addAssociation('addresses')
                ->addAssociation('addresses.country')
                ->addAssociation('addresses.countryState')
                ->addAssociation('addresses.salutation'),
            $customer
        )->getCustomer();
    }
}
