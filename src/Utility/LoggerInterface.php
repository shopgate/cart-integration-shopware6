<?php

namespace Shopgate\Shopware\Utility;

interface LoggerInterface
{
    /**
     * Information level log
     *
     * @param string $info
     */
    public function info(string $info): void;

    /**
     * Error level log
     *
     * @param string $error
     */
    public function error(string $error): void;
}
