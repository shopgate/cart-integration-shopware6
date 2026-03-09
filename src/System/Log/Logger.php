<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use ShopgateLogger;
use Symfony\Component\Serializer\SerializerInterface;

class Logger implements LoggerInterface
{
    use SafeSerializerTrait;

    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function debug($info): void
    {
        if (!is_scalar($info)) {
            $info = $this->safeSerialize($info);
        }
        ShopgateLogger::getInstance()->log($info, ShopgateLogger::LOGTYPE_DEBUG);
    }

    public function error($error): void
    {
        if (!is_scalar($error)) {
            $error = $this->safeSerialize($error);
        }
        ShopgateLogger::getInstance()->log($error);
    }

    private function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }
}
