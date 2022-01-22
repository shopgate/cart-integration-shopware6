<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping;

use Shopgate\Shopware\Shopgate\NativeOrderExtension;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use ShopgateDeliveryNote;
use ShopgateShippingMethod;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;

class ShippingMapping
{
    private ShopgateDeliveryNote $deliveryNote;

    public function __construct(ShopgateDeliveryNote $deliveryNote)
    {
        $this->deliveryNote = $deliveryNote;
    }

    /**
     * @return ShopgateShippingMethod[]
     */
    public function mapShippingMethods(DeliveryCollection $deliveries): array
    {
        $list = [];
        foreach ($deliveries->getElements() as $delivery) {
            $method = $delivery->getShippingMethod();
            $exportShipping = new ShopgateShippingMethod();
            $exportShipping->setId($method->getId());
            $exportShipping->setTitle($method->getName());
            $exportShipping->setDescription($method->getDescription());
            $exportShipping->setAmountWithTax($delivery->getShippingCosts()->getTotalPrice());
            $exportShipping->setShippingGroup(ShopgateDeliveryNote::OTHER);
            $list[$method->getId()] = $exportShipping;
        }

        return $list;
    }

    public function mapOutgoingOrderDeliveryNote(OrderDeliveryEntity $deliveryEntity): ShopgateDeliveryNote
    {
        $sgDelivery = clone $this->deliveryNote;
        $order = $deliveryEntity->getOrder();
        /** @var ?ShopgateOrderEntity $extension */
        if ($order && ($extension = $order->getExtension(NativeOrderExtension::PROPERTY))) {
            $sgDelivery->setShippingServiceId(
                $extension->getReceivedData()->getShippingInfos()->getDisplayName()
                    ?: $extension->getReceivedData()->getShippingInfos()->getName()
                    ?: 'Shipping (SG)');
        }
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
