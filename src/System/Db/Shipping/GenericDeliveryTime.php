<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Shipping;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate\Shopware\System\Db\EntityChecker;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;

$hasType = EntityChecker::checkPropertyHasType();

if (!$hasType) {
    class GenericDeliveryTime extends DeliveryTimeEntity implements ClassCastInterface
    {
        public const UUID = '49dd403eea0b4ca69e5c14894263cf2e';
        protected $id = self::UUID;
        protected $min = 10;
        protected $max = 14;
        protected $unit = parent::DELIVERY_TIME_DAY;
        protected $name = '10-14 days';

        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'min' => $this->min,
                'max' => $this->max,
                'unit' => $this->unit,
                'name' => $this->name
            ];
        }
    }
} else {
    class GenericDeliveryTime extends DeliveryTimeEntity implements ClassCastInterface
    {
        public const UUID = '49dd403eea0b4ca69e5c14894263cf2e';
        protected string $id = self::UUID;
        protected int $min = 10;
        protected int $max = 14;
        protected string $unit = parent::DELIVERY_TIME_DAY;
        protected ?string $name = '10-14 days';

        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'min' => $this->min,
                'max' => $this->max,
                'unit' => $this->unit,
                'name' => $this->name
            ];
        }
    }
}
