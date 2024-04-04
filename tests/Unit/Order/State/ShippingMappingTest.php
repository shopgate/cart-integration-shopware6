<?php declare(strict_types=1);

namespace Shopgate\Shopware\Tests\Unit\Order\State;

use DateTime;
use PHPUnit\Framework\TestCase;
use Shopgate\Shopware\Order\State\StateComposer;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class ShippingMappingTest extends TestCase
{
    /**
     * Checks that our sort gets the most recent date/time for shipping
     *
     * @dataProvider timeSortProvider
     */
    public function testDeliveryNoteTimeSort(?string $expected, array $variations): void
    {
        $toTime = static fn(string $time) => (new DateTime())->setTimestamp(strtotime($time));
        $historyCollection = new StateMachineHistoryCollection();
        $map = [];
        foreach ($variations as $date) {
            $map[$date] = $toTime($date)->format(DATE_ATOM);
            $historyCollection->set($date, (new StateMachineHistoryEntity())->assign(['createdAt' => $toTime($date)]));
        }
        $state = new StateMachineStateEntity();
        $state->setToStateMachineHistoryEntries($historyCollection);
        $time = (new StateComposer())->getStateTime($state);
        self::assertEquals(null === $expected ? $expected : $map[$expected], $time);
    }

    public function timeSortProvider(): array
    {
        return [
            'simple dates' => [
                'expected' => '+1 day',
                'variations' => ['today', '-1 day', '+1 day']
            ],
            'time check' => [
                'expected' => '+2 minutes',
                'variations' => ['now', '-1 minute', '-2 minutes', '+2 minutes', '+ 30 seconds']
            ],
            'null check' => [
                'expected' => null,
                'variations' => []
            ]
        ];
    }
}
