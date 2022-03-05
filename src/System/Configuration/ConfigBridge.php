<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Configuration;

use Shopgate\Shopware\Shopgate\ApiCredentials\ShopgateApiCredentialsEntity;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\DomainBridge;
use ShopgateLibraryException;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;

class ConfigBridge
{
    public const PLUGIN_NAMESPACE = 'SgateShopgatePluginSW6';
    public const SYSTEM_CONFIG_DOMAIN = self::PLUGIN_NAMESPACE . '.config.';
    public const SYSTEM_CONFIG_PROD_EXPORT = self::SYSTEM_CONFIG_DOMAIN . 'productTypesToExport';
    public const SYSTEM_CONFIG_IS_LIVE_SHOPPING = 'isLiveShopping';
    public const PROD_EXPORT_TYPE_SIMPLE = 'simple';
    public const PROD_EXPORT_TYPE_VARIANT = 'variant';

    private string $shopwareVersion;
    private EntityRepositoryInterface $pluginRepository;
    private ContextManager $contextManager;
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $systemConfigRepo;
    private ?SystemConfigCollection $config = null;
    private ?ShopgateApiCredentialsEntity $apiCredentialsEntity = null;
    private DomainBridge $domainBridge;
    private array $error = [];
    private EntityRepositoryInterface $shopgateApiRepo;

    public function __construct(
        EntityRepositoryInterface $pluginRepository,
        EntityRepositoryInterface $systemConfigRepo,
        EntityRepositoryInterface $shopgateApiRepo,
        string $shopwareVersion,
        ContextManager $contextManager,
        SystemConfigService $systemConfigService,
        DomainBridge $domainBridge
    ) {
        $this->pluginRepository = $pluginRepository;
        $this->shopwareVersion = $shopwareVersion;
        $this->contextManager = $contextManager;
        $this->systemConfigService = $systemConfigService;
        $this->systemConfigRepo = $systemConfigRepo;
        $this->domainBridge = $domainBridge;
        $this->shopgateApiRepo = $shopgateApiRepo;
    }

    public function getShopwareVersion(): string
    {
        return $this->shopwareVersion;
    }

    public function getShopgatePluginVersion(): string
    {
        try {
            $criteria = (new Criteria())->addFilter(new EqualsFilter('name', self::PLUGIN_NAMESPACE));
            $criteria->setTitle('shopgate::plugin::shopgate');
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
     * This is the main entry of all endpoints
     *
     * @required
     * @noinspection PhpUnused
     */
    public function loadByShopNumber($shopNumber): void
    {
        if (!$shopNumber) {
            $this->error = [
                'error' => ShopgateLibraryException::PLUGIN_API_UNKNOWN_SHOP_NUMBER,
                'error_text' => 'No shop_number property provided in the API call.'
            ];
            return;
        }

        $channel = $this->getSalesChannelConfig($shopNumber);
        if (null === $channel) {
            $this->error = [
                'error' => ShopgateLibraryException::PLUGIN_API_UNKNOWN_SHOP_NUMBER,
                'error_text' => 'No shop_number exists in the Shopgate configuration. Configure a specific channel.'
            ];
            return;
        }
        if ($channel->getActive() !== true) {
            $this->error = [
                'error' => ShopgateLibraryException::CONFIG_PLUGIN_NOT_ACTIVE,
                'error_text' => 'Plugin is not active in Shopware config'
            ];
        }
        $this->contextManager->createAndLoad($channel);
        $this->load($channel->getSalesChannelId());

    }

    /**
     * Shop number is unique to a specific SalesChannel, so we pull it here.
     */
    public function getSalesChannelConfig(string $shopNumber): ?ShopgateApiCredentialsEntity
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('shopNumber', $shopNumber)
            );
        $criteria->setTitle('shopgate::api-configurations::sales-channel-id');
        $values = $this->shopgateApiRepo->search($criteria, new Context(new SalesChannelApiSource('')));

        return $this->apiCredentialsEntity = $values->first();
    }

    /**
     * Creates a persistent cache of configurations by channel ID
     */
    public function load(string $salesChannelId): void
    {
        $criteria = (new Criteria())
            ->addFilter(new AndFilter([
                new OrFilter([
                    new EqualsFilter('salesChannelId', $salesChannelId),
                    new EqualsFilter('salesChannelId', null)
                ]),
                new ContainsFilter('configurationKey', self::SYSTEM_CONFIG_DOMAIN)
            ]));
        $criteria->addSorting(new FieldSorting('salesChannelId', FieldSorting::DESCENDING));
        /** @var SystemConfigCollection $collection */
        $collection = $this->systemConfigRepo->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->getEntities();

        $this->config = $collection;
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
        // the only time this happens is when shop_number is not provided in the request
        if (null === $this->config && null === $this->apiCredentialsEntity) {
            return $fallback;
        }
        if ($this->apiCredentialsEntity && $this->apiCredentialsEntity->has($key)) {
            return $this->apiCredentialsEntity->get($key);
        }
        /** @var ?SystemConfigEntity $config */
        $config = $this->config->filterByProperty('configurationKey', self::SYSTEM_CONFIG_DOMAIN . $key)->first();

        return $config && !empty($config->getConfigurationValue()) ? $config->getConfigurationValue() : $fallback;
    }

    /**
     * @param array|bool|float|int|string|null $value
     */
    public function set(string $key, $value): void
    {
        $context = $this->contextManager->getSalesContext();
        if ($this->apiCredentialsEntity && $this->apiCredentialsEntity->has($key)) {
            $this->shopgateApiRepo->update(
                [$this->apiCredentialsEntity->assign([$key => $value])->toArray()], $context->getContext()
            );
            return;
        }
        $this->systemConfigService->set(self::SYSTEM_CONFIG_DOMAIN . $key, $value, $context->getSalesChannelId());
    }

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

    public function getError(): array
    {
        return $this->error;
    }
}
