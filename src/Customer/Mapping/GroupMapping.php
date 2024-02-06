<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer\Mapping;

use ShopgateCustomerGroup;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;

readonly class GroupMapping
{

    public function toShopgateGroup(CustomerGroupEntity $entity): ShopgateCustomerGroup
    {
        $grp = new ShopgateCustomerGroup();
        $grp->setName($entity->getTranslation('name') ?: $entity->getName());
        $grp->setId($entity->getId());

        return $grp;
    }
}
