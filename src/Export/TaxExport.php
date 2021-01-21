<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Shopware\Core\System\Tax\TaxRuleType\EntireCountryRuleTypeFilter as EntireCountry;
use Shopware\Core\System\Tax\TaxRuleType\IndividualStatesRuleTypeFilter as IndividualStates;

class TaxExport
{
    /** @var EntityRepositoryInterface */
    private $countryRepository;
    /** @var EntityRepositoryInterface */
    private $stateRepository;
    /** @var EntityRepositoryInterface */
    private $taxRepository;
    /** @var EntityRepositoryInterface */
    private $taxRuleRepository;
    /** @var EntityRepositoryInterface */
    private $taxRuleTypeRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param EntityRepositoryInterface $countryRepository
     * @param EntityRepositoryInterface $stateRepository
     * @param EntityRepositoryInterface $taxRepository
     * @param EntityRepositoryInterface $taxRuleRepository
     * @param EntityRepositoryInterface $taxRuleTypeRepository
     * @param ContextManager $contextManager
     */
    public function __construct(
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $stateRepository,
        EntityRepositoryInterface $taxRepository,
        EntityRepositoryInterface $taxRuleRepository,
        EntityRepositoryInterface $taxRuleTypeRepository,
        ContextManager $contextManager
    ) {
        $this->countryRepository = $countryRepository;
        $this->stateRepository = $stateRepository;
        $this->taxRepository = $taxRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->taxRuleTypeRepository = $taxRuleTypeRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @return array
     * @throws MissingContextException
     */
    public function getTaxSettings(): array
    {
        $productTaxClasses = [];
        $CustomerTaxClass = ['id' => '1', 'key' => 'default2'];
        $taxRates = [];
        $taxRules = [];
        $taxFreeRateKeys = [];

        $taxFreeCountries = $this->getTaxFreeCountries();
        /** @var $taxFreeCountry CountryEntity */
        foreach ($taxFreeCountries as $taxFreeCountry) {
            $countryIso = $taxFreeCountry->getIso();
            $taxRateKey = 'rate_' . $countryIso . '_free';
            $taxRates[] = [
                'key' => $taxRateKey,
                'display_name' => '0% ' . $countryIso,
                'tax_percent' => 0,
                'country' => $countryIso,
                'state' => '',
                'zipcode_type' => 'all',
                'zipcode_pattern' => '',
                'zipcode_range_from' => '',
                'zipcode_range_to' => '',
            ];
            $taxFreeRateKeys[] = ['key' => $taxRateKey];
        }

        $shopwareTaxRates = $this->getTaxClasses();
        /** @var $shopwareTaxRate TaxEntity */
        foreach ($shopwareTaxRates as $shopwareTaxRate) {
            $taxRateKeys = [];
            $productTaxClassId = $shopwareTaxRate->getId();
            $productTaxClassKey = 'tax_' . $productTaxClassId;
            $productTaxClasses[] = [
                'id' => $productTaxClassId,
                'key' => $productTaxClassKey,
            ];

            $taxRateKey = 'rate_' . $productTaxClassId . '_default';
            $taxRates[] = [
                'key' => $taxRateKey,
                'display_name' => $shopwareTaxRate->getName(),
                'tax_percent' => $shopwareTaxRate->getTaxRate(),
                'country' => '',
                'state' => '',
                'zipcode_type' => 'all',
                'zipcode_pattern' => '',
                'zipcode_range_from' => '',
                'zipcode_range_to' => '',
            ];
            $defaultTaxRateKey = ['key' => $taxRateKey];

            $shopwareCountryTaxRules = $this->getTaxRulesByTaxId($productTaxClassId, EntireCountry::TECHNICAL_NAME);
            /** @var $shopwareTaxRule TaxRuleEntity */
            foreach ($shopwareCountryTaxRules as $shopwareTaxRule) {
                $countryId = $shopwareTaxRule->getCountryId();
                $countryIso = $this->getCountryIsoById($countryId);

                $taxRateKey = 'rate_' . $productTaxClassId . '_' . $countryIso;
                $taxRates[] = [
                    'key' => $taxRateKey,
                    'display_name' => $shopwareTaxRate->getName() . ' ' . $countryIso,
                    'tax_percent' => $shopwareTaxRule->getTaxRate(),
                    'country' => $countryIso,
                    'state' => '',
                    'zipcode_type' => 'all',
                    'zipcode_pattern' => '',
                    'zipcode_range_from' => '',
                    'zipcode_range_to' => '',
                ];
                $taxRateKeys[] = ['key' => $taxRateKey];
            }

            $shopwareStateTaxRules = $this->getTaxRulesByTaxId($productTaxClassId, IndividualStates::TECHNICAL_NAME);
            /** @var $shopwareTaxRule TaxRuleEntity */
            foreach ($shopwareStateTaxRules as $shopwareTaxRule) {
                $countryId = $shopwareTaxRule->getCountryId();
                $countryIso = $this->getCountryIsoById($countryId);
                $stateIds = $shopwareTaxRule->getData()['states'];
                foreach ($stateIds as $stateId) {
                    $stateIso = $this->getStateIsoById($stateId);
                    $taxRateKey = 'rate_' . $productTaxClassId . '_' . $countryIso . '_' . $stateIso;
                    $taxRates[] = [
                        'key' => $taxRateKey,
                        'display_name' => $shopwareTaxRate->getName() . ' ' . $countryIso . '_' . $stateIso,
                        'tax_percent' => $shopwareTaxRule->getTaxRate(),
                        'country' => $countryIso,
                        'state' => $stateIso,
                        'zipcode_type' => 'all',
                        'zipcode_pattern' => '',
                        'zipcode_range_from' => '',
                        'zipcode_range_to' => '',
                    ];
                    $taxRateKeys[] = ['key' => $taxRateKey];
                }

            }

            $taxRule = [
                'id' => $productTaxClassId,
                'key' => 'rule_' . $productTaxClassId,
                'name' => $shopwareTaxRate->getName(),
                'priority' => 0,
                'product_tax_classes' => [['key' => $productTaxClassKey]],
                'customer_tax_classes' => [$CustomerTaxClass],
                'tax_rates' => [],
            ];

            $taxRule['tax_rates'][] = $defaultTaxRateKey;
            foreach ($taxRateKeys as $taxRateKey) {
                $taxRule['tax_rates'][] = $taxRateKey;
            }
            foreach ($taxFreeRateKeys as $taxRateKeyForAll) {
                $taxRule['tax_rates'][] = $taxRateKeyForAll;
            }
            $taxRules[] = $taxRule;
        }

        return [
            'product_tax_classes' => $productTaxClasses,
            'customer_tax_classes' => [$CustomerTaxClass],
            'tax_rates' => $taxRates,
            'tax_rules' => $taxRules,
        ];
    }

    /**
     * @return array
     * @throws MissingContextException
     */
    protected function getTaxClasses(): array
    {
        return $this->taxRepository->search(new Criteria(), $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }

    /**
     * @param string $id
     * @param string $type
     * @return array
     * @throws MissingContextException
     */
    protected function getTaxRulesByTaxId(string $id, string $type): array
    {
        $typeId = $this->getTaxRuleTypeIdByTechnicalName($type);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxId', $id));
        $criteria->addFilter(new EqualsFilter('taxRuleTypeId', $typeId));

        return $this->taxRuleRepository->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }

    /**
     * @param string $technicalName
     * @return string
     * @throws MissingContextException
     */
    protected function getTaxRuleTypeIdByTechnicalName(string $technicalName): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $result = $this->taxRuleTypeRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getId();
    }

    /**
     * @return array
     * @throws MissingContextException
     */
    protected function getTaxFreeCountries(): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxFree', 1));

        return $this->countryRepository->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }

    /**
     * @param string $id
     * @return string
     * @throws MissingContextException
     */
    protected function getCountryIsoById(string $id): string
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
    protected function getStateIsoById(string $id): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $id));
        $result = $this->stateRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getShortCode();
    }
}
