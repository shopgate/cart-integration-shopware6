<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\ApiCredentials;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(ShopgateApiCredentialsEntity $entity)
 * @method void              set(string $key, ShopgateApiCredentialsEntity $entity)
 * @method ShopgateApiCredentialsEntity[]    getIterator()
 * @method ShopgateApiCredentialsEntity[]    getElements()
 * @method ShopgateApiCredentialsEntity|null get(string $key)
 * @method ShopgateApiCredentialsEntity|null first()
 * @method ShopgateApiCredentialsEntity|null last()
 */
class ShopgateApiCredentialsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ShopgateApiCredentialsEntity::class;
    }
}
