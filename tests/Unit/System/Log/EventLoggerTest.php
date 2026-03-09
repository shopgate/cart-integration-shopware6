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

    /**
     * JsonSerializable objects bypass the normalizer's circular-reference
     * tracking and cause recursion inside json_encode() itself.
     * This is the Shopware 6.7.6.1 scenario (SW6M-157).
     */
    public function testWriteLogHandlesJsonSerializableRecursion(): void
    {
        $a = new class implements \JsonSerializable {
            public object $ref;
            public function jsonSerialize(): mixed
            {
                return ['type' => 'node', 'ref' => $this->ref];
            }
        };
        $b = clone $a;
        $a->ref = $b;
        $b->ref = $a;

        $this->connection->expects($this->once())->method('executeStatement');

        $this->eventLogger->writeLog('test message', 'seq-005', Level::Info, ['obj' => $a]);
    }
}
