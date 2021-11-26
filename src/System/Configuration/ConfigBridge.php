<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Configuration;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\DomainBridge;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;

class ConfigBridge
{
    public const PLUGIN_NAMESPACE = 'SgateShopgatePluginSW6';
    public const SYSTEM_CONFIG_DOMAIN = self::PLUGIN_NAMESPACE . '.config.';
    public const SYSTEM_CONFIG_PROD_EXPORT = self::SYSTEM_CONFIG_DOMAIN . 'productTypesToExport';
    public const PROD_EXPORT_TYPE_SIMPLE = 'simple';
    public const PROD_EXPORT_TYPE_VARIANT = 'variant';

    private string $shopwareVersion;
    private EntityRepositoryInterface $pluginRepository;
    private ContextManager $contextManager;
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $systemConfigRepo;
    private array $config;
    private DomainBridge $domainBridge;

    /**
     * @param EntityRepositoryInterface $pluginRepository
     * @param string $shopwareVersion
     * @param ContextManager $contextManager
     * @param SystemConfigService $systemConfigService
     * @param EntityRepositoryInterface $systemConfigRepo
     * @param DomainBridge $domainBridge
     */
    public function __construct(
        EntityRepositoryInterface $pluginRepository,
        string $shopwareVersion,
        ContextManager $contextManager,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $systemConfigRepo,
        DomainBridge $domainBridge
    ) {
        $this->pluginRepository = $pluginRepository;
        $this->shopwareVersion = $shopwareVersion;
        $this->contextManager = $contextManager;
        $this->systemConfigService = $systemConfigService;
        $this->systemConfigRepo = $systemConfigRepo;
        $this->domainBridge = $domainBridge;
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
            $criteria->addFilter(new EqualsFilter('name', self::PLUGIN_NAMESPACE));
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
     * Loads sales channel ID using shop_number.
     * Shop_Number is in the Shopgate Plugin Config
     * for a specific channel, not "All Channels"
     *
     * @param string $shopNumber
     * @return string|null
     */
    public function getSalesChannelId(string $shopNumber): ?string
    {
        $values = $this->systemConfigRepo->search(
            (new Criteria())
                ->addFilter(
                    new EqualsFilter(
                        'configurationKey',
                        self::SYSTEM_CONFIG_DOMAIN . 'shopNumber'
                    )
                )->addFilter(new EqualsFilter(
                    'configurationValue',
                    $shopNumber
                ))
                ->addFilter(new NotFilter(
                    MultiFilter::CONNECTION_AND,
                    [new EqualsFilter('salesChannelId', null)]
                )),
            new Context(new SalesChannelApiSource(''))
        );
        if ($values->getTotal() === 1) {
            /** @var SystemConfigEntity $value */
            $value = $values->first();
            return $value->getSalesChannelId();
        }

        return null;
    }

    /**
     * Creates a persistent cache of configurations by channel ID
     *
     * @param string $salesChannelId
     * @param bool $fallback
     */
    public function load(string $salesChannelId, bool $fallback = true): void
    {
        $values = $this->systemConfigService->getDomain(
            self::SYSTEM_CONFIG_DOMAIN,
            $salesChannelId,
            $fallback
        );

        $config = [];

        foreach ($values as $key => $value) {
            $property = substr($key, strlen(self::SYSTEM_CONFIG_DOMAIN));

            $config[$property] = $value;
        }

        $this->config = $config;
    }

    /**
     * Get configuration by key as defined in config.xml
     *
     * @param string $key
     * @param array|bool|float|int|string $fallback
     * @return array|bool|float|int|string
     */
    public function get(string $key, $fallback = '')
    {
        if (!array_key_exists($key, $this->config)) {
            return $fallback;
        }

        if (empty($this->config[$key])) {
            return $fallback;
        }

        return $this->config[$key];
    }

    /**
     * @param array|bool|float|int|string|null $value
     * @throws MissingContextException
     */
    public function set(string $key, $value, SalesChannelContext $salesChannelContext = null): void
    {
        $this->systemConfigService->set(
            self::SYSTEM_CONFIG_DOMAIN . $key,
            $value,
            $salesChannelContext
                ? $salesChannelContext->getSalesChannelId()
                : $this->contextManager->getSalesContext()->getSalesChannelId());
    }

    /**
     * @param SalesChannelContext $context
     * @return string
     * @throws SalesChannelDomainNotFoundException
     */
    public function getCustomerOptInConfirmUrl(SalesChannelContext $context): string
    {
        /** @var string $domainUrl */
        $domainUrl = $this->systemConfigService
            ->get('core.loginRegistration.doubleOptInDomain', $context->getSalesChannel()->getId());
        if (!$domainUrl) {
            $domainUrl = $this->domainBridge->getDomain($context);
        }

        return $domainUrl;
    }
}
