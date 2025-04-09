<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Monolog\Level;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
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
                'context' => $this->serializer->serialize($context, 'json'),
                'extra' => $this->serializer->serialize(['sequence' => $sequence] + $extra, 'json'),
                'created' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ]
        );
    }
}
