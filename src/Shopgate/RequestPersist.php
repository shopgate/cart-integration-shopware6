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
    private ShopgateCartBase $entity;

    public function setEntity(ShopgateCartBase $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return ExtendedCart|ExtendedOrder
     */
    public function getEntity(): ShopgateCartBase
    {
        return $this->entity;
    }
}
