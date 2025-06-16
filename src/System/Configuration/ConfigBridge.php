<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Configuration;

use Shopgate\Shopware\Shopgate\ApiCredentials\ShopgateApiCredentialsEntity;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\DomainBridge;
use ShopgateLibraryException;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
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
use Symfony\Contracts\Service\Attribute\Required;
use Throwable;

class ConfigBridge
{
    public const PLUGIN_NAMESPACE = 'SgateShopgatePluginSW6';
    public const SYSTEM_CONFIG_DOMAIN = self::PLUGIN_NAMESPACE . '.config.';
    public const SYSTEM_CONFIG_PROD_EXPORT = self::SYSTEM_CONFIG_DOMAIN . 'productTypesToExport';
    public const SYSTEM_CONFIG_PROD_PROP_DOMAIN = self::SYSTEM_CONFIG_DOMAIN . 'customFieldTypeExport';
    // allows export of manufacturer details as properties
    public const PRODUCT_PROPERTY_EXPORT_MANUFACTURER = self::SYSTEM_CONFIG_DOMAIN . 'manufacturerProductProps';
    public const PRODUCT_CROSS_SELL_EXPORT = self::SYSTEM_CONFIG_DOMAIN . 'exportCrossSell';
    public const SYSTEM_CONFIG_IGNORE_SORT_ORDER = self::SYSTEM_CONFIG_DOMAIN .'ignoreSortOrderInCategories';
    public const SYSTEM_CONFIG_NET_PRICE_EXPORT = 'exportNetPrices';
    public const SYSTEM_CONFIG_IS_LIVE_SHOPPING = 'isLiveShopping';
    public const ADVANCED_CONFIG_INDEXER_STREAM_UPDATES = self::SYSTEM_CONFIG_DOMAIN . 'disableStreamUpdates';
    public const ADVANCED_CONFIG_INDEXER_WRITE_TYPE = self::SYSTEM_CONFIG_DOMAIN . 'indexerWriteType';
    public const ADVANCED_CONFIG_INDEXER_DELETE_TYPE = self::SYSTEM_CONFIG_DOMAIN . 'indexerDeleteType';
    public const ADVANCED_CONFIG_LOGGING_BASIC = self::SYSTEM_CONFIG_DOMAIN . 'basicLogging';
    public const ADVANCED_CONFIG_LOGGING_DETAIL = self::SYSTEM_CONFIG_DOMAIN . 'detailedLogging';
    // it can handle writing to a DB with same entries & update them
    public const INDEXER_WRITE_TYPE_SAFE = 'safe';
    // slightly faster DB writing in chunks, but cannot handle duplicate entries at all
    public const INDEXER_WRITE_TYPE_PERFORMANT = 'performant';
    // always deletes entries before creating/updating
    public const INDEXER_DELETE_TYPE_ALWAYS = 'always';
    // only deletes entries when all the indexers are being indexed
    public const INDEXER_DELETE_TYPE_FULL = 'full';
    // never delete entries before creating/updating
    public const INDEXER_DELETE_TYPE_NEVER = 'never';
    public const PROD_EXPORT_TYPE_SIMPLE = 'simple';
    public const PROD_EXPORT_TYPE_VARIANT = 'variant';
    public const CROSS_SELL_EXPORT_TYPE_REL = 'relations';
    public const CROSS_SELL_EXPORT_TYPE_PROPS = 'properties';

    private ?SystemConfigCollection $config = null;
    private ?ShopgateApiCredentialsEntity $apiCredentialsEntity = null;
    private array $error = [];

    public function __construct(
        private readonly EntityRepository $pluginRepository,
        private readonly EntityRepository $systemConfigRepo,
        private readonly EntityRepository $shopgateApiRepo,
        private readonly string $shopwareVersion,
        private readonly ContextManager $contextManager,
        private readonly SystemConfigService $systemConfigService,
        private readonly DomainBridge $domainBridge
    ) {
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
     */
    #[Required]
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
            ->addFilter(
                new EqualsFilter('shopNumber', $shopNumber)
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
            ->addFilter(
                new AndFilter([
                    new OrFilter([
                        new EqualsFilter('salesChannelId', $salesChannelId),
                        new EqualsFilter('salesChannelId', null)
                    ]),
                    new ContainsFilter('configurationKey', self::SYSTEM_CONFIG_DOMAIN)
                ])
            );
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
     */
    public function get(string $key, array|bool|float|int|string $fallback = ''): float|int|bool|array|string
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

        return $config && null !== $config->getConfigurationValue() ? $config->getConfigurationValue() : $fallback;
    }

    public function set(string $key, array|bool|float|int|string|null $value): void
    {
        $context = $this->contextManager->getSalesContext();
        if ($this->apiCredentialsEntity && $this->apiCredentialsEntity->has($key)) {
            $this->shopgateApiRepo->update(
                [$this->apiCredentialsEntity->assign([$key => $value])->toArray()],
                $context->getContext()
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
