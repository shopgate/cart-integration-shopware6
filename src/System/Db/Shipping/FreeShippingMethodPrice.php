<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Shipping;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryCalculator;
use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice\ShippingMethodPriceEntity;
use Shopware\Core\Defaults;

class FreeShippingMethodPrice extends ShippingMethodPriceEntity implements ClassCastInterface
{
    public const UUID = '7d421e0ec86343b39b14bebc7c4741c2';
    protected $id = self::UUID;
    protected $shippingMethodId = FreeShippingMethod::UUID;
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
