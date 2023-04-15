<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Salutations;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ShopgateSalutationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ShopgateSalutationEntity::class;
    }
}
