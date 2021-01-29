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
    /** @var LocationHelper */
    private $locationHelper;
    /** @var EntityRepositoryInterface */
    private $taxRepository;
    /** @var EntityRepositoryInterface */
    private $taxRuleRepository;
    /** @var EntityRepositoryInterface */
    private $taxRuleTypeRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param LocationHelper $locationHelper
     * @param EntityRepositoryInterface $taxRepository
     * @param EntityRepositoryInterface $taxRuleRepository
     * @param EntityRepositoryInterface $taxRuleTypeRepository
     * @param ContextManager $contextManager
     */
    public function __construct(
        LocationHelper $locationHelper,
        EntityRepositoryInterface $taxRepository,
        EntityRepositoryInterface $taxRuleRepository,
        EntityRepositoryInterface $taxRuleTypeRepository,
        ContextManager $contextManager
    ) {
        $this->locationHelper = $locationHelper;
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
        $CustomerTaxClass = ['id' => '1', 'key' => 'default', 'is_default' => '1'];
        $taxRates = [];
        $taxRules = [];
        $taxFreeRateKeys = [];

        $taxFreeCountries = $this->locationHelper->getTaxFreeCountries();
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
            foreach ($shopwareCountryTaxRules as $shopwareTaxRule) {
                $countryId = $shopwareTaxRule->getCountryId();
                $countryIso = $this->locationHelper->getCountryIsoById($countryId);

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
            foreach ($shopwareStateTaxRules as $shopwareTaxRule) {
                $countryId = $shopwareTaxRule->getCountryId();
                $countryIso = $this->locationHelper->getCountryIsoById($countryId);
                $stateIds = $shopwareTaxRule->getData()['states'];
                foreach ($stateIds as $stateId) {
                    $stateIso = $this->locationHelper->getStateIsoById($stateId);
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
     * @return TaxEntity[]
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
     * @return TaxRuleEntity[]
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
}
