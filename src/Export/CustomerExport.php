<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CustomerExport
{
    /** @var EntityRepositoryInterface */
    private $customerGroupRepository;
    /** @var EntityRepositoryInterface */
    private $salutationRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param EntityRepositoryInterface $customerGroupRepository
     * @param EntityRepositoryInterface $salutationRepository
     * @param ContextManager $contextManager
     */
    public function __construct(
        EntityRepositoryInterface $customerGroupRepository,
        EntityRepositoryInterface $salutationRepository,
        ContextManager $contextManager
    ) {
        $this->customerGroupRepository = $customerGroupRepository;
        $this->salutationRepository = $salutationRepository;
        $this->contextManager = $contextManager;
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
     * @return string
     * @throws MissingContextException
     */
    public function getMaleSalutationId(): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', 'mr'));
        $result = $this->salutationRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : $this->getUnspecifiedSalutationId();
    }

    /**
     * @return string
     * @throws MissingContextException
     */
    public function getFemaleSalutationId(): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', 'mrs'));
        $result = $this->salutationRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : $this->getUnspecifiedSalutationId();
    }

    /**
     * @return string
     * @throws MissingContextException
     */
    public function getUnspecifiedSalutationId(): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salutationKey', 'not_specified'));
        $result = $this->salutationRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : $this->getAnySalutationId();
    }

    /**
     * This is the last fallback and should not be needed
     * Return any SalutationId as it is required to register customers
     *
     * @return string
     * @throws MissingContextException
     */
    public function getAnySalutationId(): string
    {
        $result = $this->salutationRepository->search(
            new Criteria(),
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getId();
    }
}
