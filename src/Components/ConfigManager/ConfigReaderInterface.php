<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Components\ConfigManager;

interface ConfigReaderInterface
{
    public function read(string $salesChannelId = '', bool $fallback = true);

    public function get(string $key, $default = '');
}
