<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\CountryEntity;

class LocationMapping
{
    private EntityRepositoryInterface $countryRepository;
    private EntityRepositoryInterface $stateRepository;
    private ContextManager $contextManager;

    public function __construct(
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $stateRepository,
        ContextManager $contextManager
    ) {
        $this->countryRepository = $countryRepository;
        $this->stateRepository = $stateRepository;
        $this->contextManager = $contextManager;
    }

    public function getCountryIsoById(string $id): ?string
    {
        $criteria = new Criteria([$id]);
        $criteria->setTitle('shopgate::country::id');
        /** @var CountryEntity|null $result */
        $result = $this->countryRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getIso() : null;
    }

    public function getStateIsoById(string $id): ?string
    {
        $criteria = new Criteria([$id]);
        $criteria->setTitle('shopgate::state::id');
        $result = $this->stateRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getShortCode() : null;
    }

    public function getCountryIdByIso(string $code): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('iso', $code));
        $criteria->setTitle('shopgate::country::iso');
        $result = $this->countryRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : '';
    }

    public function getStateIdByIso(?string $code): string
    {
        if (empty($code)) {
            return '';
        }
        $criteria = (new Criteria())->addFilter(new EqualsFilter('shortCode', $code));
        $criteria->setTitle('shopgate::state::iso');
        $result = $this->stateRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result ? $result->getId() : '';
    }

    /**
     * @return CountryEntity[]
     */
    public function getTaxFreeCountries(): array
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('taxFree', 1));
        $criteria->setTitle('shopgate::country::tax-free');

        return $this->countryRepository->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }
}
