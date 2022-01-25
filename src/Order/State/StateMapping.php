<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\State;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class StateMapping
{
    public function isShipped(StateMachineStateEntity $state): bool
    {
        return in_array($state->getTechnicalName(),
            [OrderDeliveryStates::STATE_SHIPPED, OrderDeliveryStates::STATE_PARTIALLY_SHIPPED],
            true);
    }

    /**
     * Most accurate time stamps come from history, don't forget to add criteria->association
     */
    public function getShippingTime(StateMachineStateEntity $state): ?string
    {
        $time = $state->getCreatedAt() ? $state->getCreatedAt()->format(DATE_ATOM) : null;
        if ($history = $state->getToStateMachineHistoryEntries()) {
            $history->sort(fn(
                StateMachineHistoryEntity $one,
                StateMachineHistoryEntity $two
            ) => $two->getCreatedAt() <=> $one->getCreatedAt());
            $mostRecent = $history->first();
            $time = $mostRecent && $mostRecent->getCreatedAt() ? $mostRecent->getCreatedAt()->format(DATE_ATOM) : $time;
        }

        return $time;
    }
}
