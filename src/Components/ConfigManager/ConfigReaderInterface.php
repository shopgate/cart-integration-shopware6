<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Components\ConfigManager;

interface ConfigReaderInterface
{
    public const SYSTEM_CONFIG_DOMAIN = 'ShopgateModule.config.';
    public const PROD_EXPORT_TYPE_SIMPLE = 'simple';
    public const PROD_EXPORT_TYPE_VARIANT = 'variant';

    /**
     * Loads sales channel ID using shop_number.
     * Shop_Number is in the Shopgate Plugin Config
     * for a specific channel, not "All Channels"
     *
     * @param string $shopNumber
     * @return string|null
     */
    public function getSalesChannelId(string $shopNumber): ?string;

    /**
     * Creates a persistent cache of configurations by channel ID
     *
     * @param string $salesChannelId
     * @param bool $fallback
     */
    public function load(string $salesChannelId, bool $fallback = true): void;

    /**
     * Get configuration by key as defined in config.xml
     *
     * @param string $key
     * @param string $fallback
     * @return array|bool|float|int|string
     */
    public function get(string $key, $fallback = '');
}
