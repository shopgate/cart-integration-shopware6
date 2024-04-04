<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Mapping;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;

class LocationMapping
{

    public function __construct(
        private readonly EntityRepository $countryRepository,
        private readonly EntityRepository $stateRepository,
        private readonly ContextManager $contextManager
    ) {
    }

    public function getCountryIsoById(string $id): ?string
    {
        $criteria = new Criteria([$id]);
        $criteria->setTitle('shopgate::country::id');
        /** @var ?CountryEntity $result */
        $result = $this->countryRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result?->getIso();
    }

    public function getStateIsoById(string $id): ?string
    {
        $criteria = new Criteria([$id]);
        $criteria->setTitle('shopgate::state::id');
        /** @var ?CountryStateEntity $result */
        $result = $this->stateRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result?->getShortCode();
    }

    public function getCountryIdByIso(string $code): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('iso', $code));
        $criteria->setTitle('shopgate::country::iso');
        /** @var ?CountryEntity $result */
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
        /** @var ?CountryStateEntity $result */
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
        $criteria = (new Criteria())->addFilter(new ContainsFilter('customerTax', '"amount": 0'));
        $criteria->setTitle('shopgate::country::customer-tax-free');

        return $this->countryRepository->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }
}
