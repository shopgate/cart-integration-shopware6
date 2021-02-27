<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Mapping;

use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use ShopgateDeliveryNote;
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
            if ($delivery->getShippingMethod()->getId() === GenericShippingMethod::UUID) {
                continue;
            }
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
}
