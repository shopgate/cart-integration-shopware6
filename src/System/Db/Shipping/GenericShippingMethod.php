<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Shipping;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate\Shopware\System\Db\EntityChecker;
use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

$hasType = EntityChecker::checkPropertyHasType();

if (!$hasType) {
    class GenericShippingMethod extends ShippingMethodEntity implements ClassCastInterface
    {
        public const UUID = '368e891dbec442c2892f82edd6f4a7dc';
        protected $id = self::UUID;
        protected $deliveryTimeId = GenericDeliveryTime::UUID;
        protected $name = 'Generic Shipping (SG)';
        protected $description = '';
        protected $active = false;

        public function __construct()
        {
            parent::__construct();
            $this->setAvailabilityRuleId(IsShopgateRuleGroup::UUID);
        }

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
} else {
    class GenericShippingMethod extends ShippingMethodEntity implements ClassCastInterface
    {
        public const UUID = '368e891dbec442c2892f82edd6f4a7dc';
        protected string $id = self::UUID;
        protected string $deliveryTimeId = GenericDeliveryTime::UUID;
        protected ?string $name = 'Generic Shipping (SG)';
        protected ?string $description = '';
        protected ?bool $active = false;
        protected string $technicalName = 'sg_generic_shipping';

        public function __construct()
        {
            parent::__construct();
            $this->setAvailabilityRuleId(IsShopgateRuleGroup::UUID);
        }

        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'deliveryTimeId' => $this->deliveryTimeId,
                'name' => $this->name,
                'description' => $this->description,
                'active' => $this->active,
                'availabilityRuleId' => $this->availabilityRuleId,
                'technicalName' => $this->technicalName
            ];
        }
    }
}

