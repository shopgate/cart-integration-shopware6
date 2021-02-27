<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

interface LoggerInterface
{
    /**
     * Debug level log
     *
     * @param string $info
     */
    public function debug(string $info): void;

    /**
     * Error level log
     *
     * @param string $error
     */
    public function error(string $error): void;
}
