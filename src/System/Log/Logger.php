<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use ShopgateLogger;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class Logger implements LoggerInterface
{

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

    private function safeSerialize(mixed $data): string
    {
        try {
            return $this->serializer->serialize($data, 'json', $this->getSerializerContext());
        } catch (\Throwable) {
            return json_encode($this->sanitize($data), \JSON_THROW_ON_ERROR);
        }
    }

    private function sanitize(mixed $data, int $depth = 0): mixed
    {
        if ($depth > 10) {
            return '[max depth]';
        }
        if ($data === null || is_scalar($data)) {
            return $data;
        }
        if (is_object($data)) {
            return '[object] ' . get_class($data);
        }
        if (is_array($data)) {
            $out = [];
            foreach ($data as $k => $v) {
                $out[$k] = $this->sanitize($v, $depth + 1);
            }
            return $out;
        }
        return '[' . get_debug_type($data) . ']';
    }

    private function getSerializerContext(): array
    {
        return [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => static function (object $object): string {
                return get_class($object);
            },
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['session'],
            JsonEncode::OPTIONS => \JSON_PARTIAL_OUTPUT_ON_ERROR,
        ];
    }
}
