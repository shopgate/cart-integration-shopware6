<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\State;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class StateBridge
{
    private StateMachineRegistry $stateMachineRegistry;

    public function __construct(StateMachineRegistry $stateMachineRegistry)
    {
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * api/_action/state-machine/{entityName}/{entityId}/state/{transition}
     * @param string $entityName - order_transaction , order_delivery, order
     * @param string $entityId - orderTransaction->id, orderDelivery->id, order->id
     * @param string $transition - see `State` classes for constants or check state_machine_transition->action DB
     */
    public function transition(
        string $entityName,
        string $entityId,
        string $transition,
        Context $context,
        string $stateFieldName = 'stateId'
    ): ?StateMachineStateEntity {
        $stateMachineStateCollection = $this->stateMachineRegistry->transition(
            new Transition(
                $entityName,
                $entityId,
                $transition,
                $stateFieldName
            ),
            $context
        );

        return $stateMachineStateCollection->get('toPlace');
    }
}
