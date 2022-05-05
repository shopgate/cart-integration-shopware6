<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopgate_Model_Catalog_Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;

class PriceMapping
{
    private bool $exportNet;

    public function __construct(bool $exportNet)
    {
        $this->exportNet = $exportNet;
    }

    public function getPriceType(): string
    {
        return $this->exportNet
            ? Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET
            : Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS;
    }

    public function mapPrice(Price $price): float
    {
        $getNetOrGross = $this->exportNet ? 'getNet' : 'getGross';

        return $price->$getNetOrGross();
    }
}