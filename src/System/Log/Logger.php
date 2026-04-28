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
        ShopgateLogger::getInstance()->log($this->normalizeLogPayload($info), ShopgateLogger::LOGTYPE_DEBUG);
    }

    public function error($error): void
    {
        ShopgateLogger::getInstance()->log($this->normalizeLogPayload($error));
    }

    private function normalizeLogPayload(mixed $value): mixed
    {
        return is_scalar($value) ? $value : $this->safeSerialize($value);
    }

    private function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }
}
