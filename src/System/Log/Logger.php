<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use ShopgateLogger;
use Symfony\Component\Serializer\SerializerInterface;

class Logger implements LoggerInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function debug($info): void
    {
        if (!is_scalar($info)) {
            $info = $this->serializer->serialize($info, 'json');
        }
        ShopgateLogger::getInstance()->log($info, ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * @inheritdoc
     */
    public function error($error): void
    {
        if (!is_scalar($error)) {
            $error = $this->serializer->serialize($error, 'json');
        }
        ShopgateLogger::getInstance()->log($error);
    }
}
