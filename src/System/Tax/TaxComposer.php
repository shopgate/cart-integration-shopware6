<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Tax;

use Shopgate\Shopware\Customer\Mapping\LocationMapping;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopware\Core\System\Tax\TaxRuleType\EntireCountryRuleTypeFilter as EntireCountry;
use Shopware\Core\System\Tax\TaxRuleType\IndividualStatesRuleTypeFilter as IndividualStates;

class TaxComposer
{
    /** @var LocationMapping */
    private $locationMapping;
    /** @var TaxBridge */
    private $taxBridge;

    /**
     * @param LocationMapping $locationMapping
     * @param TaxBridge $taxBridge
     */
    public function __construct(LocationMapping $locationMapping, TaxBridge $taxBridge)
    {
        $this->locationMapping = $locationMapping;
        $this->taxBridge = $taxBridge;
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

        $taxFreeCountries = $this->locationMapping->getTaxFreeCountries();
        foreach ($taxFreeCountries as $taxFreeCountry) {
            $countryIso = $taxFreeCountry->getIso();
            if (!$countryIso) {
                continue;
            }
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

        $shopwareTaxRates = $this->taxBridge->getTaxClasses();
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

            $shopwareCountryTaxRules = $this->taxBridge->getTaxRulesByTaxId(
                $productTaxClassId,
                EntireCountry::TECHNICAL_NAME
            );
            foreach ($shopwareCountryTaxRules as $shopwareTaxRule) {
                $countryId = $shopwareTaxRule->getCountryId();
                $countryIso = $this->locationMapping->getCountryIsoById($countryId);
                if (!$countryIso) {
                    continue;
                }

                $taxRateKey = implode('_', ['rate', $productTaxClassId, $countryIso]);
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

            $shopwareStateTaxRules = $this->taxBridge->getTaxRulesByTaxId(
                $productTaxClassId,
                IndividualStates::TECHNICAL_NAME
            );
            foreach ($shopwareStateTaxRules as $shopwareTaxRule) {
                $countryId = $shopwareTaxRule->getCountryId();
                $countryIso = $this->locationMapping->getCountryIsoById($countryId);
                if (!$countryIso) {
                    continue;
                }
                $stateIds = $shopwareTaxRule->getData()['states'];
                foreach ($stateIds as $stateId) {
                    $stateIso = $this->locationMapping->getStateIsoById($stateId);
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
}
