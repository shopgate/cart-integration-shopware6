<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Components\ConfigManager;

use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigReader implements ConfigReaderInterface
{
    /** @var SystemConfigService */
    private $systemConfigService;
    /** @var EntityRepositoryInterface */
    private $systemConfigRepo;
    /** @var array */
    private $config;

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepositoryInterface $systemConfigRepo
     */
    public function __construct(SystemConfigService $systemConfigService, EntityRepositoryInterface $systemConfigRepo)
    {
        $this->systemConfigService = $systemConfigService;
        $this->systemConfigRepo = $systemConfigRepo;
    }

    /**
     * @inheritDoc
     */
    public function getSalesChannelId(string $shopNumber): ?string
    {
        $values = $this->systemConfigRepo->search(
            (new Criteria())
                ->addFilter(
                    new ContainsFilter(
                        'configurationKey',
                        self::SYSTEM_CONFIG_DOMAIN . 'shopNumber'
                    )
                )->addFilter(new ContainsFilter(
                    'configurationValue',
                    $shopNumber
                ))
                ->addFilter(new NotFilter(
                    NotFilter::CONNECTION_AND,
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
     * @inheritDoc
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
     * @inheritDoc
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
}
