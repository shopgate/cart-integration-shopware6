<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Shipping;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate\Shopware\System\Db\EntityChecker;
use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

$hasType = EntityChecker::checkPropertyHasType();

if (!$hasType) {
    class FreeShippingMethod extends ShippingMethodEntity implements ClassCastInterface
    {
        public const UUID = '92929e3cb97141f1b70ab9a8616df6a7';
        protected $id = self::UUID;
        protected $deliveryTimeId = GenericDeliveryTime::UUID;
        protected $name = 'Free Shipping (SG)';
        protected $description = '';
        protected $active = false;

        public function __construct()
        {
            parent::__construct();
            $this->setAvailabilityRuleId(IsShopgateRuleGroup::UUID);
        }

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
} else {
    class FreeShippingMethod extends ShippingMethodEntity implements ClassCastInterface
    {
        public const UUID = '92929e3cb97141f1b70ab9a8616df6a7';
        protected string $id = self::UUID;
        protected string $deliveryTimeId = GenericDeliveryTime::UUID;
        protected ?string $name = 'Free Shipping (SG)';
        protected ?string $description = '';
        protected ?bool $active = false;
        protected string $technicalName = 'shopgate_free_shipping';

        public function __construct()
        {
            parent::__construct();
            $this->setAvailabilityRuleId(IsShopgateRuleGroup::UUID);
        }

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
                'availabilityRuleId' => $this->availabilityRuleId,
                'technicalName' => $this->technicalName
            ];
        }
    }
}
