<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Taxes;

use Shopgate\Shopware\Catalog\Product\ProductBridge;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\System\CurrencyComposer;
use Shopgate\Shopware\System\Formatter;
use ShopgateExternalOrderTax;
use ShopgateOrderItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

readonly class TaxMapping
{

    public function __construct(
        private CurrencyComposer $currencyComposer,
        private ExtendedClassFactory $classFactory,
        private Formatter $formatter,
        private ProductBridge $productBridge
    ) {
    }

    /**
     * @return float[] - [withTax, withoutTax]
     */
    public function calculatePrices(CalculatedPrice $price, ?string $taxStatus): array
    {
        $tax = $price->getTotalPrice() > 0 ? $price->getCalculatedTaxes()->getAmount() : 0;
        if ($taxStatus === CartPrice::TAX_STATE_GROSS) {
            $priceWithTax = $price->getUnitPrice();
            $priceWithoutTax = $price->getUnitPrice() - ($tax / $price->getQuantity());
        } else {
            $priceWithoutTax = $price->getUnitPrice();
            $priceWithTax = $price->getUnitPrice() + ($tax / $price->getQuantity());
        }

        return [
            $this->currencyComposer->roundAsItem($priceWithTax),
            $this->currencyComposer->roundAsItem($priceWithoutTax)
        ];
    }

    public function mapOutgoingOrderTaxes(CalculatedTax $swTax): ShopgateExternalOrderTax
    {
        $sgTax = $this->classFactory->createExternalOrderTax();
        $sgTax->setAmount($this->currencyComposer->roundAsItem($swTax->getTax()));
        $sgTax->setTaxPercent($swTax->getTaxRate());
        $sgTax->setLabel($this->formatter->translate('checkout.summaryTax', ['%rate%' => $swTax->getTaxRate()]));

        return $sgTax;
    }

    public function getPriceTaxRate(CalculatedPrice $price): float
    {
        $tax = $price->getCalculatedTaxes()
            ->filter(fn(CalculatedTax $price) => $price->getTax() !== 0.0)->sortByTax()->first();

        return $tax ? $tax->getTaxRate() : 0.0;
    }

    /** @noinspection PhpCastIsUnnecessaryInspection */
    public function mapTaxRate(ShopgateOrderItem $incItem, array $cartItemIds, SalesChannelContext $context): array
    {
        $product = $this->productBridge->getSimplifiedProductList($cartItemIds)->get($incItem->getItemNumber());
        if (!$product) {
            return [];
        }
        $definition = new QuantityPriceDefinition(
            (float)$incItem->getUnitAmount(),
            $context->buildTaxRules($product->getTaxId()),
            (int)$incItem->getQuantity()
        );

        return array_map(static fn(TaxRule $taxRule) => $taxRule->getVars(), $definition->getTaxRules()->getElements());
    }
}
