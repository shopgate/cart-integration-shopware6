<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\State;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class StateComposer
{
    public function isPaid(?StateMachineStateEntity $state): bool
    {
        return $state && $state->getTechnicalName() === OrderTransactionStates::STATE_PAID;
    }

    public function isAtLeastPartialShipped(?StateMachineStateEntity $state): bool
    {
        return $state && in_array($state->getTechnicalName(),
                [OrderDeliveryStates::STATE_SHIPPED, OrderDeliveryStates::STATE_PARTIALLY_SHIPPED],
                true);
    }

    public function isFullyShipped(?StateMachineStateEntity $state): bool
    {
        return $state && $state->getTechnicalName() === OrderDeliveryStates::STATE_SHIPPED;
    }

    public function isComplete(?StateMachineStateEntity $state): bool
    {
        return $state && $state->getTechnicalName() === OrderStates::STATE_COMPLETED;
    }

    public function isCancelled(?StateMachineStateEntity $state): bool
    {
        return $state && $state->getTechnicalName() === OrderStates::STATE_CANCELLED;
    }

    /**
     * Most accurate time stamps come from history, don't forget to add criteria->association
     */
    public function getStateTime(StateMachineStateEntity $state): ?string
    {
        $time = $state->getCreatedAt() ? $state->getCreatedAt()->format(DATE_ATOM) : null;
        if ($history = $state->getToStateMachineHistoryEntries()) {
            $mostRecent = $this->getLastHistoryState($history);
            $time = $mostRecent && $mostRecent->getCreatedAt() ? $mostRecent->getCreatedAt()->format(DATE_ATOM) : $time;
        }

        return $time;
    }

    public function getLastHistoryState(?StateMachineHistoryCollection $history): ?StateMachineHistoryEntity
    {
        if (null === $history) {
            return null;
        }
        $history->sort(fn(
            StateMachineHistoryEntity $one,
            StateMachineHistoryEntity $two
        ) => $two->getCreatedAt() <=> $one->getCreatedAt());

        return $history->first();
    }
}
