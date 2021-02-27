<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Order;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ShopgateOrderEntity $entity)
 * @method void              set(string $key, ShopgateOrderEntity $entity)
 * @method ShopgateOrderEntity[]    getIterator()
 * @method ShopgateOrderEntity[]    getElements()
 * @method ShopgateOrderEntity|null get(string $key)
 * @method ShopgateOrderEntity|null first()
 * @method ShopgateOrderEntity|null last()
 */
class ShopgateOrderCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ShopgateOrderEntity::class;
    }
}
