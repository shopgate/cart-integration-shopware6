<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Monolog\Level;
use Shopware\Core\Framework\Uuid\Uuid;

class EventLogger
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @throws Exception
     */
    public function writeLog(string $message, string $sequence, Level $level = Level::Info): void
    {
        $this->db->executeStatement(
            'INSERT INTO `log_entry` (`id`, `message`, `level`, `channel`, `created_at`) VALUES (:id, :message, :level, :channel, now())',
            [
                'id' => Uuid::randomBytes(),
                'message' => "Shopgate Go ($sequence): " . $message,
                'level' => $level->value,
                'channel' => 'Shopgate Go',
            ]
        );
    }
}
