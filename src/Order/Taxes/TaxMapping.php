<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Taxes;

use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\System\Formatter;
use ShopgateExternalOrderTax;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;

class TaxMapping
{
    private ExtendedClassFactory $classFactory;
    private Formatter $formatter;

    public function __construct(ExtendedClassFactory $classFactory, Formatter $formatter)
    {
        $this->classFactory = $classFactory;
        $this->formatter = $formatter;
    }

    public function calculatePrices(
        CalculatedPrice $price,
        ?string $taxStatus
    ): array {
        $tax = $price->getCalculatedTaxes()->getAmount();
        if ($taxStatus === CartPrice::TAX_STATE_GROSS) {
            $priceWithTax = $price->getUnitPrice();
            $priceWithoutTax = $price->getUnitPrice() - ($tax / $price->getQuantity());
        } else {
            $priceWithoutTax = $price->getUnitPrice();
            $priceWithTax = $price->getUnitPrice() + ($tax / $price->getQuantity());
        }

        return [$priceWithTax, $priceWithoutTax];
    }

    public function mapOutgoingOrderTaxes(CalculatedTax $swTax): ShopgateExternalOrderTax
    {
        $sgTax = $this->classFactory->createExternalOrderTax();
        $sgTax->setAmount($swTax->getTax());
        $sgTax->setTaxPercent($swTax->getTaxRate());
        $sgTax->setLabel($this->formatter->translate(
            'checkout.summaryTax',
            ['%rate%' => $swTax->getTaxRate()]
        ));

        return $sgTax;
    }
}
