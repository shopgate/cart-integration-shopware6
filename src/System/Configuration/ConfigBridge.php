<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Configuration;

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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
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
    public const SYSTEM_CONFIG_IS_ACTIVE = 'isActive';
    public const PROD_EXPORT_TYPE_SIMPLE = 'simple';
    public const PROD_EXPORT_TYPE_VARIANT = 'variant';

    private string $shopwareVersion;
    private EntityRepositoryInterface $pluginRepository;
    private ContextManager $contextManager;
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $systemConfigRepo;
    private ?SystemConfigCollection $config = null;
    private DomainBridge $domainBridge;
    private array $error = [];

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

    public function getShopwareVersion(): string
    {
        return $this->shopwareVersion;
    }

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

        $channel = $this->getSalesChannelId($shopNumber);
        if (null === $channel) {
            $this->error = [
                'error' => ShopgateLibraryException::PLUGIN_API_UNKNOWN_SHOP_NUMBER,
                'error_text' => 'No shop_number exists in the Shopgate configuration. Configure a specific channel.'
            ];
            return;
        }
        $this->contextManager->createAndLoadByChannelId($channel);
        $this->load($channel);
        if ($this->get(self::SYSTEM_CONFIG_IS_ACTIVE) !== true) {
            $this->error = [
                'error' => ShopgateLibraryException::CONFIG_PLUGIN_NOT_ACTIVE,
                'error_text' => 'Plugin is not active in Shopware config'
            ];
        }
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
        // the only time this happens is when an incorrect shop_number is not provided in the request
        if (null === $this->config) {
            return $fallback;
        }
        /** @var ?SystemConfigEntity $config */
        $config = $this->config->filterByProperty('configurationKey', self::SYSTEM_CONFIG_DOMAIN . $key)->first();

        return $config && !empty($config->getConfigurationValue()) ? $config->getConfigurationValue() : $fallback;
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
