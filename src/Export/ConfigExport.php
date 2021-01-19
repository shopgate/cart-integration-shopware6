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
    private $customerGroupRepository;
    /** @var EntityRepositoryInterface */
    private $taxRepository;
    /** @var EntityRepositoryInterface */
    private $taxRuleRepository;
    /** @var EntityRepositoryInterface */
    private $pluginRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * ConfigExport constructor.
     * @param EntityRepositoryInterface $customerGroupRepository
     * @param EntityRepositoryInterface $taxRepository
     * @param EntityRepositoryInterface $taxRuleRepository
     * @param EntityRepositoryInterface $pluginRepository
     * @param string $shopwareVersion
     * @param ContextManager $contextManager
     */
    public function __construct(
        EntityRepositoryInterface $customerGroupRepository,
        EntityRepositoryInterface $taxRepository,
        EntityRepositoryInterface $taxRuleRepository,
        EntityRepositoryInterface $pluginRepository,
        string $shopwareVersion,
        ContextManager $contextManager
    )
    {
        $this->customerGroupRepository = $customerGroupRepository;
        $this->taxRepository = $taxRepository;
        $this->taxRuleRepository = $taxRuleRepository;
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
        $shopwareTaxRates = $this->getAllElements($this->taxRepository);
        $productTaxClasses = [];
        /** @var $shopwareRate \Shopware\Core\System\Tax\TaxEntity */
        foreach ($shopwareTaxRates as $id => $shopwareTaxRate) {
            $productTaxClasses[] = [
                'id' => $id,
                'key' => $shopwareTaxRate->getName(),
            ];
        }

        $shopwareTaxRules = $this->getAllElements($this->taxRuleRepository);

        /** @var $shopwareTaxRule \Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity */
        foreach ($shopwareTaxRules as $id => $shopwareTaxRule) {
            $countryId = $shopwareTaxRule->getCountryId();
            $taxId = $shopwareTaxRule->getTaxId();
        }
        // todo-rainer implement
        return [
            "product_tax_classes"  => $productTaxClasses,
            "customer_tax_classes" => [['id' => '1', 'key' => 'default', 'is_default' => '1']],
            "tax_rates"            => [],
            "tax_rules"            => [],
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
}
