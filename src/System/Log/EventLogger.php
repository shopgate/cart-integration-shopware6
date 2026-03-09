<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Monolog\Level;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class EventLogger
{
    public function __construct(private readonly Connection $db, private readonly SerializerInterface $serializer)
    {
    }

    /**
     * @throws Exception
     */
    public function writeLog(
        string $message,
        string $sequence,
        Level $level = Level::Info,
        array $context = [],
        array $extra = []
    ): void {
        $this->db->executeStatement(
            'INSERT INTO `log_entry` (`id`, `message`, `level`, `channel`, `context`, `extra`, `created_at`) VALUES (:id, :message, :level, :channel, :context, :extra, :created)',
            [
                'id' => Uuid::randomBytes(),
                'message' => "Shopgate Go ($sequence): " . $message,
                'level' => $level->value,
                'channel' => 'Shopgate Go',
                'context' => $this->safeSerialize($context),
                'extra' => $this->safeSerialize(['sequence' => $sequence] + $extra),
                'created' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ]
        );
    }

    private function safeSerialize(mixed $data): string
    {
        try {
            return $this->serializer->serialize($data, 'json', $this->getSerializerContext());
        } catch (\Throwable) {
            return json_encode($this->sanitize($data), \JSON_THROW_ON_ERROR);
        }
    }

    /**
     * Reduces data to scalars only — used as fallback when Symfony
     * serializer fails due to recursion in JsonSerializable objects
     * that bypass the normalizer's circular-reference tracking.
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