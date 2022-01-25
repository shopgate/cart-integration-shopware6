<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Order;

use Shopgate\Shopware\Shopgate\NativeOrderExtension;
use Shopware\Core\Checkout\Order\OrderEntity;

class ShopgateOrderMapping
{
    public function getShopgateOrder(OrderEntity $order): ?ShopgateOrderEntity
    {
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        /** @var ?ShopgateOrderEntity $extension */
        $extension = $order->getExtension(NativeOrderExtension::PROPERTY);

        return $extension;
    }

    /**
     * @param OrderEntity|ShopgateOrderEntity|null $orderEntity
     * @return string
     */
    public function getShippingMethodName($orderEntity): string
    {
        $default = 'Shipping (SG)';
        if ($orderEntity instanceof OrderEntity) {
            $orderEntity = $this->getShopgateOrder($orderEntity);
        }

        if (null === $orderEntity) {
            return $default;
        }

        return $orderEntity->getReceivedData()->getShippingInfos()->getDisplayName()
            ?: $orderEntity->getReceivedData()->getShippingInfos()->getName()
                ?: $default;
    }
}
