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
    private $pluginRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param EntityRepositoryInterface $pluginRepository
     * @param string $shopwareVersion
     * @param ContextManager $contextManager
     */
    public function __construct(
        EntityRepositoryInterface $pluginRepository,
        string $shopwareVersion,
        ContextManager $contextManager
    ) {
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
        // todo-rainer implement
        return [];
    }

    /**
     * @return array
     */
    public function getTaxSettings(): array
    {
        // todo-rainer implement
        return [];
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
}
