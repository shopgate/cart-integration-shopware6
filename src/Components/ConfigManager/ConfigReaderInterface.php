<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Components\ConfigManager;

interface ConfigReaderInterface
{
    public const PROD_EXPORT_TYPE_SIMPLE = 'simple';
    public const PROD_EXPORT_TYPE_VARIANT = 'variant';

    public function read(string $salesChannelId = '', bool $fallback = true);

    public function get(string $key, $default = '');
}
