<?php

namespace Shopgate\Shopware\Order\Mapping;

use ShopgateShippingMethod;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;

class ShippingMapping
{
    /**
     * @param DeliveryCollection $deliveries
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
            $exportShipping->setShippingGroup('SHOPGATE');
            $list[$method->getId()] = $exportShipping;
        }

        return $list;
    }
}
