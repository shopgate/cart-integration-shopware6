<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping;

use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderMapping;
use ShopgateDeliveryNote;
use ShopgateExternalOrderExtraCost;
use ShopgateShippingMethod;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;

class ShippingMapping
{

    private ExtendedClassFactory $classFactory;
    private TaxMapping $taxMapping;
    private ShopgateOrderMapping $shopgateOrderMapping;

    public function __construct(
        ExtendedClassFactory $classFactory,
        TaxMapping $taxMapping,
        ShopgateOrderMapping $shopgateOrderMapping
    ) {
        $this->classFactory = $classFactory;
        $this->taxMapping = $taxMapping;
        $this->shopgateOrderMapping = $shopgateOrderMapping;
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
        $sgDelivery->setShippingTime(
            $deliveryEntity->getCreatedAt() ? $deliveryEntity->getCreatedAt()->format(DATE_ATOM) : null
        );
        if ($state = $deliveryEntity->getStateMachineState()) {
            $isShipped = in_array($state->getTechnicalName(),
                [OrderDeliveryStates::STATE_SHIPPED, OrderDeliveryStates::STATE_PARTIALLY_SHIPPED],
                true);
            $backupTime = $isShipped && $state->getCreatedAt() ? $state->getCreatedAt()->format(DATE_ATOM) : null;
            $sgDelivery->setShippingTime($backupTime);
            $history = $state->getToStateMachineHistoryEntries()
                ? $state->getToStateMachineHistoryEntries()->first()
                : null;
            if ($isShipped && $history && $history->getCreatedAt()) {
                $sgDelivery->setShippingTime($history->getCreatedAt()->format(DATE_ATOM));
            }
        }

        return $sgDelivery;
    }
}
