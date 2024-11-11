<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Log;

use DateTimeZone;
use Doctrine\DBAL\Exception;
use Monolog\Level;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FallbackLogger extends \Monolog\Logger
{
    private string $sequence;

    public function __construct(
        string $name,
        private readonly SystemConfigService $systemConfigService,
        private readonly EventLogger $eventLogger,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null,
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

    /**
     * @throws Exception
     */
    public function writeEvent(string $message, Level $level = Level::Info): void
    {
        $this->eventLogger->writeLog($message, $this->sequence, $level);
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
