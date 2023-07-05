<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use ShopgateCartBase;

/**
 * Holds incoming Shopgate objects in singleton/global memory, so we can
 * pass it via the DI system.
 */
class RequestPersist
{
    private ExtendedCart|ExtendedOrder $entity;

    public function setEntity(ExtendedCart|ExtendedOrder $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity(): ExtendedCart|ExtendedOrder
    {
        return $this->entity;
    }
}
