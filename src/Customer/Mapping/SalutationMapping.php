<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCustomer;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Salutation\SalutationEntity;

class SalutationMapping
{
    private EntityRepositoryInterface $salutationRepository;
    private ContextManager $contextManager;

    public function __construct(EntityRepositoryInterface $salutationRepository, ContextManager $contextManager)
    {
        $this->salutationRepository = $salutationRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @param string $gender
     * @return string
     * @throws MissingContextException
     */
    public function getSalutationIdByGender(string $gender): string
    {
        switch ($gender) {
            case ShopgateCustomer::MALE:
                return $this->getMaleSalutationId();
            case ShopgateCustomer::FEMALE:
                return $this->getFemaleSalutationId();
            default:
                return $this->getUnspecifiedSalutationId();
        }
    }

    /**
     * @return string
     * @throws MissingContextException
     */
    public function getMaleSalutationId(): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', 'mr'));
        $criteria->setTitle('shopgate::salutation::male');
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
        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', 'not_specified'));
        $criteria->setTitle('shopgate::salutation::unspecified');
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
        $criteria = new Criteria();
        $criteria->setTitle('shopgate::salutation::any');
        $result = $this->salutationRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getId();
    }

    /**
     * @return string
     * @throws MissingContextException
     */
    public function getFemaleSalutationId(): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', 'mrs'));
        $criteria->setTitle('shopgate::salutation::female');
        $result = $this->salutationRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : $this->getUnspecifiedSalutationId();
    }

    /**
     * @param SalutationEntity $entity
     * @return string|null
     */
    public function toShopgateGender(SalutationEntity $entity): ?string
    {
        switch ($entity->getSalutationKey()) {
            case 'mr':
                return ShopgateCustomer::MALE;
            case 'mrs':
                return ShopgateCustomer::FEMALE;
            default:
                return null;
        }
    }
}
