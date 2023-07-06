<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Order;

use Shopgate\Shopware\Shopgate\NativeOrderExtension;
use Shopware\Core\Checkout\Order\OrderEntity;

class ShopgateOrderMapping
{
    public function getShopgateOrder(OrderEntity $order): ?ShopgateOrderEntity
    {
        /** @var ?ShopgateOrderEntity $extension */
        $extension = $order->getExtension(NativeOrderExtension::PROPERTY);

        return $extension;
    }

    public function getShippingMethodName(OrderEntity|ShopgateOrderEntity|null $orderEntity): string
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
