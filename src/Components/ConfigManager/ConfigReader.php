<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Components\ConfigManager;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigReader implements ConfigReaderInterface
{
    public const SYSTEM_CONFIG_DOMAIN = 'ShopgateModule.config.';

    /** @var SystemConfigService */
    private $systemConfigService;

    private $config;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function read(?string $salesChannelId = null, bool $fallback = true)
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

        $this->config= $config;
    }

    public function get(string $key, $default = '')
    {
        if (!array_key_exists($key, $this->config)) {
            return $default;
        }

        if (empty($this->config[$key])) {
            return $default;
        }

        return $this->config[$key];
    }
}
