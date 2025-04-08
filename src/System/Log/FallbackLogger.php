<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use DateTimeZone;
use Monolog\Level;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;

class FallbackLogger extends \Monolog\Logger
{
    private string $sequence;

    public function __construct(
        string $name,
        private readonly SystemConfigService $systemConfigService,
        private readonly EventLogger $eventLogger,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        $this->sequence = Uuid::randomHex();
        parent::__construct($name, $handlers, $processors, $timezone);
    }

    public function logDetails(string $message, array $context = []): void
    {
        if ($this->systemConfigService->getFloat(ConfigBridge::ADVANCED_CONFIG_LOGGING_DETAIL)) {
            $this->logToFile($message, $context);
        }
    }

    public function logBasics(string $message, array $context = []): void
    {
        if ($this->systemConfigService->getFloat(ConfigBridge::ADVANCED_CONFIG_LOGGING_BASIC)) {
            $this->logToFile($message, $context);
        }
    }

    public function writeThrowableEvent(Throwable $error, $level = Level::Critical): void
    {
        $this->writeEvent($error->getMessage(), $level, [
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString()
        ]);
    }

    /**
     * Writes to database event log.
     * These messages can be looked up in admin panel.
     */
    public function writeEvent(string $message, Level $level = Level::Info, array $context = []): void
    {
        if (!$this->systemConfigService->getFloat(ConfigBridge::ADVANCED_CONFIG_LOGGING_BASIC)) {
            return;
        }

        try {
            $this->eventLogger->writeLog($message, $this->sequence, $level, $context);
        } catch (Throwable $e) {
            $this->debug('Could not write to DB. ' . $e->getMessage());
            $this->debug($message);
        }
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
