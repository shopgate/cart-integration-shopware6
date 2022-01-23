<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping;

use Shopgate\Shopware\Order\State\StateMapping;
use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderMapping;
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
    private StateMapping $stateMapping;

    public function __construct(
        ExtendedClassFactory $classFactory,
        TaxMapping $taxMapping,
        ShopgateOrderMapping $shopgateOrderMapping,
        StateMapping $stateMapping
    ) {
        $this->classFactory = $classFactory;
        $this->taxMapping = $taxMapping;
        $this->shopgateOrderMapping = $shopgateOrderMapping;
        $this->stateMapping = $stateMapping;
    }

    public function mapOutCartShippingMethod(Delivery $delivery): ShopgateShippingMethod
    {
        $method = $delivery->getShippingMethod();
        $exportShipping = $this->classFactory->createShippingMethod();
        $exportShipping->setId($method->getId());
        $exportShipping->setTitle($method->getName());
        $exportShipping->setDescription($method->getDescription());
        $exportShipping->setAmountWithTax($delivery->getShippingCosts()->getTotalPrice());
        $exportShipping->setShippingGroup(ShopgateDeliveryNote::OTHER);

        return $exportShipping;
    }

    public function mapOutOrderShippingMethod(OrderDeliveryEntity $deliveryEntity): ShopgateExternalOrderExtraCost
    {
        $sgExport = $this->classFactory->createOrderExtraCost();
        $sgExport->setAmount($deliveryEntity->getShippingCosts()->getTotalPrice());
        $sgExport->setType(ShopgateExternalOrderExtraCost::TYPE_SHIPPING);
        $sgExport->setTaxPercent($this->taxMapping->getPriceTaxRate($deliveryEntity->getShippingCosts()));
        $sgExport->setLabel($this->shopgateOrderMapping->getShippingMethodName($deliveryEntity->getOrder()));

        return $sgExport;
    }

    public function mapOutgoingOrderDeliveryNote(OrderDeliveryEntity $deliveryEntity): ShopgateDeliveryNote
    {
        $sgDelivery = $this->classFactory->createDeliveryNote();
        $sgDelivery->setShippingServiceId($this->shopgateOrderMapping->getShippingMethodName($deliveryEntity->getOrder()));
        $sgDelivery->setTrackingNumber(implode(', ', $deliveryEntity->getTrackingCodes()));

        if ($state = $deliveryEntity->getStateMachineState()) {
            $isShipped = $this->stateMapping->isShipped($state);
            $shippedDate = $this->stateMapping->getShippingTime($state);
            $sgDelivery->setShippingTime($isShipped ? $shippedDate : null);
        }

        return $sgDelivery;
    }
}
