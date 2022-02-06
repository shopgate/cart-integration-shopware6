<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping;

use Shopgate\Shopware\Order\State\StateComposer;
use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderMapping;
use Shopgate\Shopware\System\Formatter;
use ShopgateDeliveryNote;
use ShopgateExternalOrderExtraCost;
use ShopgateShippingMethod;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;

class ShippingMapping
{
    private ExtendedClassFactory $classFactory;
    private TaxMapping $taxMapping;
    private ShopgateOrderMapping $shopgateOrderMapping;
    private StateComposer $stateMapping;
    private Formatter $formatter;

    public function __construct(
        ExtendedClassFactory $classFactory,
        TaxMapping $taxMapping,
        ShopgateOrderMapping $shopgateOrderMapping,
        StateComposer $stateMapping,
        Formatter $formatter
    ) {
        $this->classFactory = $classFactory;
        $this->taxMapping = $taxMapping;
        $this->shopgateOrderMapping = $shopgateOrderMapping;
        $this->stateMapping = $stateMapping;
        $this->formatter = $formatter;
    }

    public function mapOutCartShippingMethod(Delivery $delivery): ShopgateShippingMethod
    {
        $method = $delivery->getShippingMethod();
        $exportShipping = $this->classFactory->createShippingMethod();
        $exportShipping->setId($method->getId());
        $exportShipping->setTitle($method->getTranslation('name') ?: $method->getName());
        $exportShipping->setDescription($method->getTranslation('description') ?: $method->getDescription());
        $exportShipping->setAmountWithTax($delivery->getShippingCosts()->getTotalPrice());
        $exportShipping->setShippingGroup(ShopgateDeliveryNote::OTHER);

        return $exportShipping;
    }

    public function mapOutOrderShippingMethod(OrderDeliveryEntity $deliveryEntity): ShopgateExternalOrderExtraCost
    {
        $price = $deliveryEntity->getShippingCosts()->getTotalPrice();
        $sgExport = $this->classFactory->createOrderExtraCost();
        $sgExport->setAmount($price);
        $sgExport->setType(ShopgateExternalOrderExtraCost::TYPE_SHIPPING);
        $sgExport->setTaxPercent($this->taxMapping->getPriceTaxRate($deliveryEntity->getShippingCosts()));
        $label = $this->formatter->translate('sg-quote.summaryLabelShippingCosts', [], null);
        $sgExport->setLabel($label);

        return $sgExport;
    }

    public function mapOutgoingOrderDeliveryNote(OrderDeliveryEntity $deliveryEntity): ShopgateDeliveryNote
    {
        $sgDelivery = $this->classFactory->createDeliveryNote();
        $sgDelivery->setShippingServiceId($this->shopgateOrderMapping->getShippingMethodName($deliveryEntity->getOrder()));
        $sgDelivery->setTrackingNumber(implode(', ', $deliveryEntity->getTrackingCodes()));

        if (($state = $deliveryEntity->getStateMachineState()) && $this->stateMapping->isAtLeastPartialShipped($state)) {
            $shippedDate = $this->stateMapping->getStateTime($state);
            $sgDelivery->setShippingTime($shippedDate ?: null);
        }

        return $sgDelivery;
    }
}
