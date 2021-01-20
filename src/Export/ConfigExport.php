<?php

namespace Shopgate\Shopware\Export;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Throwable;

class ConfigExport
{
    /** @var string */
    private $shopwareVersion;
    /** @var EntityRepositoryInterface */
    private $countryRepository;
    /** @var EntityRepositoryInterface */
    private $stateRepository;
    /** @var EntityRepositoryInterface */
    private $customerGroupRepository;
    /** @var EntityRepositoryInterface */
    private $taxRepository;
    /** @var EntityRepositoryInterface */
    private $taxRuleRepository;
    /** @var EntityRepositoryInterface */
    private $taxRuleTypeRepository;
    /** @var EntityRepositoryInterface */
    private $pluginRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * ConfigExport constructor.
     * @param EntityRepositoryInterface $countryRepository
     * @param EntityRepositoryInterface $stateRepository
     * @param EntityRepositoryInterface $customerGroupRepository
     * @param EntityRepositoryInterface $taxRepository
     * @param EntityRepositoryInterface $taxRuleRepository
     * @param EntityRepositoryInterface $taxRuleTypeRepository
     * @param EntityRepositoryInterface $pluginRepository
     * @param string $shopwareVersion
     * @param ContextManager $contextManager
     */
    public function __construct(
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $stateRepository,
        EntityRepositoryInterface $customerGroupRepository,
        EntityRepositoryInterface $taxRepository,
        EntityRepositoryInterface $taxRuleRepository,
        EntityRepositoryInterface $taxRuleTypeRepository,
        EntityRepositoryInterface $pluginRepository,
        string $shopwareVersion,
        ContextManager $contextManager
    )
    {
        $this->countryRepository = $countryRepository;
        $this->stateRepository = $stateRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->taxRepository = $taxRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->taxRuleTypeRepository = $taxRuleTypeRepository;
        $this->pluginRepository = $pluginRepository;
        $this->shopwareVersion = $shopwareVersion;
        $this->contextManager = $contextManager;
    }

    /**
     * @return string
     */
    public function getShopwareVersion(): string
    {
        return $this->shopwareVersion;
    }

    /**
     * @return string
     */
    public function getShopgatePluginVersion(): string
    {
        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', 'ShopgateModule'));
            $result = $this->pluginRepository->search(
                $criteria,
                $this->contextManager->getSalesContext()->getContext()
            )->first();
            $version = $result->getVersion();
        } catch (Throwable $e) {
            $version = 'not installed';
        }
        return $version;
    }

    /**
     * @return array
     */
    public function getCustomerGroups(): array
    {
        // todo-rainer move this to customerExport class
        $defaultCustomerGroupId = $this->contextManager->getSalesContext()->getCurrentCustomerGroup()->getId();
        $customerGroups = $this->getAllElements($this->customerGroupRepository);

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
     * @return array
     */
    public function getTaxSettings(): array
    {
        // todo-rainer move this to taxExport class
        $productTaxClasses  = [];
        $CustomerTaxClass   = ['id' => '1', 'key'=> 'default'];
        $taxRates           = [];
        $taxRules           = [];
        $taxFreeRateKeys  = [];

        $taxFreeCountries = $this->getTaxFreeCountries();
        /** @var $taxFreeCountry \Shopware\Core\System\Country\CountryEntity */
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

        $shopwareTaxRates = $this->getAllElements($this->taxRepository);
        /** @var $shopwareTaxRate \Shopware\Core\System\Tax\TaxEntity */
        foreach ($shopwareTaxRates as $shopwareTaxRate) {
            $taxRateKeys = [];
            $productTaxClassId = $shopwareTaxRate->getId();
            $productTaxClassKey = 'tax_' .  $productTaxClassId;
            $productTaxClasses[] = [
                'id' => $productTaxClassId,
                'key' => $productTaxClassKey,
            ];

            $taxRateKey = 'rate_' .  $productTaxClassId . '_default';
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

            $shopwareCountryTaxRules = $this->getTaxRulesByTaxId($productTaxClassId, 'entire_country');
            /** @var $shopwareTaxRule \Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity */
            foreach ($shopwareCountryTaxRules as $shopwareTaxRule) {
                $countryId = $shopwareTaxRule->getCountryId();
                $countryIso = $this->getCountryIsoById($countryId);

                $taxRateKey = 'rate_' .  $productTaxClassId . '_' . $countryIso;
                $taxRates[]                       = [
                    'key'                => $taxRateKey,
                    'display_name'       => $shopwareTaxRate->getName() . ' ' . $countryIso,
                    'tax_percent'        => $shopwareTaxRule->getTaxRate(),
                    'country'            => $countryIso,
                    'state'              => '',
                    'zipcode_type'       => 'all',
                    'zipcode_pattern'    => '',
                    'zipcode_range_from' => '',
                    'zipcode_range_to'   => '',
                ];
                $taxRateKeys[] = ['key' => $taxRateKey];
            }

            $shopwareStateTaxRules = $this->getTaxRulesByTaxId($productTaxClassId, 'individual_states');
            /** @var $shopwareTaxRule \Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity */
            foreach ($shopwareStateTaxRules as $shopwareTaxRule) {
                $countryId = $shopwareTaxRule->getCountryId();
                $countryIso = $this->getCountryIsoById($countryId);
                $stateIds =  $shopwareTaxRule->getData()['states'];
                foreach ($stateIds as $stateId) {
                    $stateIso = $this->getStateIsoById($stateId);
                    $taxRateKey = 'rate_' .  $productTaxClassId . '_' . $countryIso . '_' . $stateIso;
                    $taxRates[]                       = [
                        'key'                => $taxRateKey,
                        'display_name'       => $shopwareTaxRate->getName() . ' ' . $countryIso . '_' . $stateIso,
                        'tax_percent'        => $shopwareTaxRule->getTaxRate(),
                        'country'            => $countryIso,
                        'state'              => $stateIso,
                        'zipcode_type'       => 'all',
                        'zipcode_pattern'    => '',
                        'zipcode_range_from' => '',
                        'zipcode_range_to'   => '',
                    ];
                    $taxRateKeys[] = ['key' => $taxRateKey];
                }

            }

            $taxRule             = [
                'id'                   => $productTaxClassId,
                'key'                  => 'rule_' . $productTaxClassId,
                'name'                 => $shopwareTaxRate->getName(),
                'priority'             => 0,
                'product_tax_classes'  => [['key' => $productTaxClassKey]],
                'customer_tax_classes' => [$CustomerTaxClass],
                'tax_rates'            => [],
            ];

            $taxRule['tax_rates'][] = $defaultTaxRateKey;
            foreach ($taxRateKeys as $taxRateKey) {
                $taxRule['tax_rates'][] = $taxRateKey;
            }
            foreach ($taxFreeRateKeys as $taxRateKeyForAll) {
                $taxRule['tax_rates'][] = $taxRateKeyForAll;
            }
            $taxRules[]             = $taxRule;
        }

        return [
            'product_tax_classes'  => $productTaxClasses,
            'customer_tax_classes' => [$CustomerTaxClass],
            'tax_rates'            => $taxRates,
            'tax_rules'            => $taxRules,
        ];
    }

    /**
     * @return array
     */
    public function getAllowedBillingCountries(): array
    {
        // todo-rainer implement
        return [];
    }

    /**
     * @return array
     */
    public function getAllowedShippingCountries(): array
    {
        // todo-rainer implement
        return [];
    }

    /**
     * @return array
     */
    public function getAllPaymentMethods(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getAllElements(EntityRepositoryInterface $repo): array
    {
        return $repo->search(new Criteria(), $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }

    /**
     * @param string $id
     * @param string $type
     * @return array
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
     */
    protected function getTaxRuleTypeIdByTechnicalName(string $technicalName): string
    {
        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
            $result = $this->taxRuleTypeRepository->search(
                $criteria,
                $this->contextManager->getSalesContext()->getContext()
            )->first();
            $id = $result->getId();
        } catch (Throwable $e) {
            // todo-rainer error handling
            $id = 'not found';
        }
        return $id;
    }

    /**
     * @return array
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
     */
    protected function getCountryIsoById(string $id): string
    {
        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('id', $id));
            $result = $this->countryRepository->search(
                $criteria,
                $this->contextManager->getSalesContext()->getContext()
            )->first();
            $countryIso = $result->getIso();
        } catch (Throwable $e) {
            // todo-rainer error handling
            $countryIso = 'unknown';
        }
        return $countryIso;
    }

    /**
     * @param string $id
     * @return string
     */
    protected function getStateIsoById(string $id): string
    {
        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('id', $id));
            $result = $this->stateRepository->search(
                $criteria,
                $this->contextManager->getSalesContext()->getContext()
            )->first();
            $countryIso = $result->getShortCode();
        } catch (Throwable $e) {
            // todo-rainer error handling
            $countryIso = 'unknown';
        }
        return $countryIso;
    }
}
