<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

interface LoggerInterface
{
    /**
     * Debug level log
     *
     * @param mixed $info
     */
    public function debug($info): void;

    /**
     * Error level log
     *
     * @param mixed $error
     */
    public function error($error): void;
}
