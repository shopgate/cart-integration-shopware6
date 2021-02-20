<?php

namespace Shopgate\Shopware\System\Log;

use ShopgateLogger;

class Logger implements LoggerInterface
{
    /**
     * @inheritdoc
     */
    public function debug(string $info): void
    {
        ShopgateLogger::getInstance()->log($info, ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * @inheritdoc
     */
    public function error(string $error): void
    {
        ShopgateLogger::getInstance()->log($error, ShopgateLogger::LOGTYPE_ERROR);
    }
}
