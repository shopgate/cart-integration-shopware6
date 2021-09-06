<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;

/**
 * Holds incoming Shopgate objects in singleton/global memory, so we can
 * pass it via the DI system.
 */
class RequestPersist
{
    private ExtendedOrder $incomingOrder;

    public function getIncomingOrder(): ExtendedOrder
    {
        return $this->incomingOrder;
    }

    public function setIncomingOrder(ExtendedOrder $order): RequestPersist
    {
        $this->incomingOrder = $order;

        return $this;
    }
}
