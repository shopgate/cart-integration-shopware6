<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

trait SafeSerializerTrait
{
    abstract private function getSerializer(): SerializerInterface;

    private function safeSerialize(mixed $data): string
    {
        try {
            return $this->getSerializer()->serialize($data, 'json', [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => static fn(object $object): string => get_class($object),
                AbstractNormalizer::IGNORED_ATTRIBUTES => [
                    'session',
                    'container',
                    'kernel',
                    'entityManager',
                    'extensions',
                ],
                JsonEncode::OPTIONS => \JSON_PARTIAL_OUTPUT_ON_ERROR,
            ]);
        } catch (\Throwable) {
            return json_encode($this->sanitize($data), \JSON_THROW_ON_ERROR);
        }
    }

    /**
     * Fallback when Symfony serializer fails due to recursion in
     * JsonSerializable objects that bypass normalizer tracking.
     */
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
}
