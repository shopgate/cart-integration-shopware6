<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer;

use Shopgate\Shopware\Shopgate\SalutationExtension;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractCustomerRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractLoginRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

readonly class CustomerBridge
{

    public function __construct(
        private EntityRepository      $customerGroupRepository,
        private EntityRepository      $customerRepository,
        private ContextManager        $contextManager,
        private AbstractLoginRoute    $loginRoute,
        private RequestDataBag        $dataBag,
        private AbstractCustomerRoute $customerRoute
    )
    {
    }

    public function getGroups(): CustomerGroupCollection|EntityCollection
    {
        $criteria = new Criteria();
        $criteria->setTitle('shopgate::customer-group');
        return $this->customerGroupRepository
            ->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities();
    }

    /**
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
                $e->getMessage(),
                false,
                false
            );
        } catch (Throwable $throwable) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_CUSTOMER_UNKNOWN_ERROR,
                $throwable->getMessage(),
                false,
                true
            );
        }
    }

    public function getDetailedContextCustomer(SalesChannelContext $context): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setId($context->getCustomer()?->getId());
        $criteria = (new Criteria())->setLimit(1)
            ->addAssociation('group')
            ->addAssociation('salutation')
            ->addAssociation('salutation.' . SalutationExtension::PROPERTY)
            ->addAssociation('addresses')
            ->addAssociation('addresses.country')
            ->addAssociation('addresses.countryState')
            ->addAssociation('addresses.salutation')
            ->addAssociation('addresses.salutation.' . SalutationExtension::PROPERTY);
        $criteria->setTitle('shopgate::customer::detailed');

        return $this->customerRoute->load(new Request(), $context, $criteria, $customer)->getCustomer();
    }

    public function getGuestByEmail(string $email, SalesChannelContext $context): ?CustomerEntity
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('email', $email))
            ->addFilter(new EqualsFilter('guest', 1));
        $criteria->setTitle('shopgate::customer::guest');
        $results = $this->customerRepository->search($criteria, $context->getContext());

        return $results->getEntities()->first();
    }
}
