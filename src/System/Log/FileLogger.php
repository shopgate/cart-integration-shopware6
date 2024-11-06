<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use DateTimeZone;
use Shopware\Core\Framework\Uuid\Uuid;

class FileLogger extends \Monolog\Logger
{
    private string $sequence;

    public function __construct(
        string $name,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        $this->sequence = Uuid::randomHex();
        parent::__construct($name, $handlers, $processors, $timezone);
    }

    public function logDetails(string $message, array $context = []): void
    {
        // todo: check config to see if logging should be done
//        $this->logToFile($message, $context);
    }

    public function logBasics(string $message, array $context = []): void
    {
        // todo: check config to see if logging should be done
        $this->logToFile($message, $context);
    }

    /**
     * File storage logging for details
     */
    private function logToFile(string $message, array $context = []): void
    {
        $context = ['sequence' => $this->sequence] + $context;
        if (count($context) > 50) {
            $context = ['truncated' => true] + array_slice($context, 50);
        }
        $this->debug($message, $context);
    }
}
