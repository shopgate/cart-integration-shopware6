<?php

namespace Shopgate\Shopware\Customer\Mapping;

use ShopgateCustomerGroup;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;

class GroupMapping
{

    /**
     * @param CustomerGroupEntity $entity
     * @return ShopgateCustomerGroup
     */
    public function toShopgateGroup(CustomerGroupEntity $entity): ShopgateCustomerGroup
    {
        $grp = new ShopgateCustomerGroup();
        $grp->setName($entity->getName());
        $grp->setId($entity->getId());
        return $grp;
    }
}
