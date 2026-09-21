<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Shipping;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate\Shopware\System\Db\EntityChecker;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryCalculator;
use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice\ShippingMethodPriceEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;

$hasType = EntityChecker::checkPropertyHasType();

if (!$hasType) {
    class GenericShippingMethodPrice extends ShippingMethodPriceEntity implements ClassCastInterface
    {
        public const UUID = '4c1f9ed1540c47dab734ca6b059621ee';
        protected $id = self::UUID;
        protected $shippingMethodId = GenericShippingMethod::UUID;
        protected $quantityStart = 0;
        protected $calculation = DeliveryCalculator::CALCULATION_BY_PRICE;
        protected $currencyPrice = [
            [
                'net' => 0,
                'gross' => 0,
                'linked' => false,
                'currencyId' => Defaults::CURRENCY,
                'listPrice' => null
            ]
        ];

        /**
         * @return array<string, mixed>
         */
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'shippingMethodId' => $this->shippingMethodId,
                'calculation' => $this->calculation,
                'quantityStart' => $this->quantityStart,
                'currencyPrice' => $this->currencyPrice
            ];
        }
    }
} else {
    class GenericShippingMethodPrice extends ShippingMethodPriceEntity implements ClassCastInterface
    {
        public const UUID = '4c1f9ed1540c47dab734ca6b059621ee';

        protected string $id = self::UUID;
        protected string $shippingMethodId = GenericShippingMethod::UUID;
        protected ?float $quantityStart = 0;
        protected ?int $calculation = DeliveryCalculator::CALCULATION_BY_PRICE;
        protected ?PriceCollection $currencyPrice;

        public function __construct()
        {
            $this->currencyPrice = new PriceCollection([
                new Price(Defaults::CURRENCY, 0.0, 0.0, false)
            ]);
        }

        /**
         * @return array<string, mixed>
         */
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'shippingMethodId' => $this->shippingMethodId,
                'calculation' => $this->calculation,
                'quantityStart' => $this->quantityStart,
                'currencyPrice' => $this->currencyPrice
            ];
        }
    }
}
