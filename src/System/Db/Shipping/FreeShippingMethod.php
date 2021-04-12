<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Shipping;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

class FreeShippingMethod extends ShippingMethodEntity implements ClassCastInterface
{
    public const UUID = '92929e3cb97141f1b70ab9a8616df6a7';
    protected $id = self::UUID;
    protected $deliveryTimeId = GenericDeliveryTime::UUID;
    protected $name = 'Free Shipping (SG)';
    protected $description = 'Used for Free Shopgate Shipping';
    protected $availabilityRuleId = IsShopgateRuleGroup::UUID;
    protected $active = false;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'deliveryTimeId' => $this->deliveryTimeId,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'availabilityRuleId' => $this->availabilityRuleId
        ];
    }
}
