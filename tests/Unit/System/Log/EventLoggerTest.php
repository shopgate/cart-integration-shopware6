<?php declare(strict_types=1);

namespace Shopgate\Shopware\Tests\Unit\System\Log;

use Doctrine\DBAL\Connection;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Shopgate\Shopware\System\Log\EventLogger;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EventLoggerTest extends TestCase
{
    private Connection $connection;
    private EventLogger $eventLogger;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $this->eventLogger = new EventLogger($this->connection, $serializer);
    }

    /**
     * H-A: $context containing objects with circular references must not crash writeLog.
     * The DB entry should still be written (log not silently dropped).
     */
    public function testWriteLogHandlesCircularReferenceInContext(): void
    {
        $circular = new \stdClass();
        $circular->name = 'context-test';
        $circular->self = $circular; // circular reference

        $this->connection->expects($this->once())->method('executeStatement');

        $this->eventLogger->writeLog('test message', 'seq-001', Level::Info, ['obj' => $circular]);
    }

    /**
     * H-B: $extra containing circular references must also be handled gracefully.
     */
    public function testWriteLogHandlesCircularReferenceInExtra(): void
    {
        $circular = new \stdClass();
        $circular->name = 'extra-test';
        $circular->ref = $circular; // circular reference

        $this->connection->expects($this->once())->method('executeStatement');

        $this->eventLogger->writeLog('test message', 'seq-002', Level::Info, [], ['obj' => $circular]);
    }

    /**
     * Sanity check: plain scalar data must always produce a DB write.
     */
    public function testWriteLogWithScalarDataSucceeds(): void
    {
        $this->connection->expects($this->once())->method('executeStatement');

        $this->eventLogger->writeLog('test message', 'seq-003', Level::Info, ['key' => 'value', 'count' => 42]);
    }

    /**
     * Deeply nested circular reference (chain A -> B -> A) must also be handled.
     */
    public function testWriteLogHandlesDeepCircularReference(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $a->child = $b;
        $b->parent = $a; // indirect circular reference

        $this->connection->expects($this->once())->method('executeStatement');

        $this->eventLogger->writeLog('test message', 'seq-004', Level::Info, ['a' => $a]);
    }
}
