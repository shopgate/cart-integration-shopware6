<?php

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\CountryEntity;

class LocationMapping
{
    /** @var EntityRepositoryInterface */
    private $countryRepository;
    /** @var EntityRepositoryInterface */
    private $stateRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param EntityRepositoryInterface $countryRepository
     * @param EntityRepositoryInterface $stateRepository
     * @param ContextManager $contextManager
     */
    public function __construct(
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $stateRepository,
        ContextManager $contextManager
    ) {
        $this->countryRepository = $countryRepository;
        $this->stateRepository = $stateRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @param string $id
     * @return string
     * @throws MissingContextException
     */
    public function getCountryIsoById(string $id): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $result = $this->countryRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getIso();
    }

    /**
     * @param string $id
     * @return string
     * @throws MissingContextException
     */
    public function getStateIsoById(string $id): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $result = $this->stateRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getShortCode();
    }

    /**
     * @param string $code
     * @return string
     * @throws MissingContextException
     */
    public function getCountryIdByIso(string $code): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $code));
        $result = $this->countryRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : '';
    }

    /**
     * @param string|null $code
     * @return string
     * @throws MissingContextException
     */
    public function getStateIdByIso(?string $code): string
    {
        if (empty($code)) {
            return '';
        }
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', $code));
        $result = $this->stateRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : '';
    }

    /**
     * @return CountryEntity[]
     * @throws MissingContextException
     */
    public function getTaxFreeCountries(): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxFree', 1));

        return $this->countryRepository->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }
}
