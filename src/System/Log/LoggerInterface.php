<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

interface LoggerInterface
{
    /**
     * Debug level log
     */
    public function debug(mixed $info): void;

    /**
     * Error level log
     */
    public function error(mixed $error): void;
}
