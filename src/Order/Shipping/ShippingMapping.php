<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping;

use Shopgate\Shopware\Order\State\StateComposer;
use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethod;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use Shopgate\Shopware\System\Formatter;
use ShopgateDeliveryNote;
use ShopgateExternalOrderExtraCost;
use ShopgateShippingMethod;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;

readonly class ShippingMapping
{

    public function __construct(
        private ExtendedClassFactory $classFactory,
        private TaxMapping $taxMapping,
        private ShopgateOrderMapping $shopgateOrderMapping,
        private StateComposer $stateMapping,
        private ContextManager $contextManager,
        private Formatter $formatter
    ) {
    }

    public function mapOutCartShippingMethod(Delivery $delivery): ShopgateShippingMethod
    {
        $costs = $delivery->getShippingCosts();
        $method = $delivery->getShippingMethod();
        $taxStatus = $this->contextManager->getSalesContext()->getTaxState();
        $exportShipping = $this->classFactory->createShippingMethod();
        $exportShipping->setId($method->getId());
        $exportShipping->setTitle($method->getTranslation('name') ?: $method->getName());
        $exportShipping->setDescription($method->getTranslation('description') ?: $method->getDescription());
        [$priceWithTax, $priceWithoutTax] = $this->taxMapping->calculatePrices($costs, $taxStatus);
        $exportShipping->setAmount(max($priceWithoutTax, 0));
        $exportShipping->setAmountWithTax(max($priceWithTax, 0));
        $exportShipping->setShippingGroup(ShopgateDeliveryNote::OTHER);
        if ($highestRate = $costs->getTaxRules()->highestRate()) {
            $exportShipping->setTaxPercent($highestRate->getTaxRate());
        } elseif ($anyTax = $costs->getCalculatedTaxes()->sortByTax()->first()) {
            $exportShipping->setTaxPercent($anyTax->getTaxRate());
        }

        return $exportShipping;
    }

    public function mapOutOrderShippingMethod(
        CalculatedPrice $shippingCosts,
        string $taxStatus
    ): ShopgateExternalOrderExtraCost {
        $sgExport = $this->classFactory->createOrderExtraCost();
        [$withTax,] = $this->taxMapping->calculatePrices($shippingCosts, $taxStatus);
        $sgExport->setAmount($withTax); // always gross return
        $sgExport->setType(ShopgateExternalOrderExtraCost::TYPE_SHIPPING);
        $sgExport->setTaxPercent($this->taxMapping->getPriceTaxRate($shippingCosts));
        $label = $this->formatter->translate('sg-quote.summaryLabelShippingCosts', [], null);
        $sgExport->setLabel($label);

        return $sgExport;
    }

    public function mapOutgoingOrderDeliveryNote(OrderDeliveryEntity $deliveryEntity): ShopgateDeliveryNote
    {
        $sgDelivery = $this->classFactory->createDeliveryNote();
        $sgDelivery->setShippingServiceId(ShopgateDeliveryNote::OTHER);
        $sgDelivery->setShippingServiceName(
            $this->shopgateOrderMapping->getShippingMethodName($deliveryEntity->getOrder())
        );
        $sgDelivery->setTrackingNumber(implode(', ', $deliveryEntity->getTrackingCodes()));

        if (($state = $deliveryEntity->getStateMachineState()) && $this->stateMapping->isAtLeastPartialShipped(
                $state
            )) {
            $shippedDate = $this->stateMapping->getStateTime($state);
            $sgDelivery->setShippingTime($shippedDate ?: null);
        }

        return $sgDelivery;
    }

    public function getShopwareShippingId(ExtendedCart|ExtendedOrder $quote, ?string $taxState): string
    {
        if ($quote->isShopwareShipping()) {
            return $quote->getShippingId();
        }
        $isShippingFree = $quote->hasShippingInfo() && $quote->isShippingFree($taxState);

        return $isShippingFree ? FreeShippingMethod::UUID : GenericShippingMethod::UUID;
    }
}
