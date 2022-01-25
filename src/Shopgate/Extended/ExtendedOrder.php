<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateContainerToArrayVisitor;
use ShopgateExternalCoupon;
use ShopgateOrder;
use ShopgateOrderItem;

class ExtendedOrder extends ShopgateOrder
{
    use CloningTrait;
    use CartUtilityTrait;

    protected ShopgateExternalCoupon $externalCoupon;
    protected ShopgateOrderItem $orderItem;
    private ShopgateContainerToArrayVisitor $visitor;

    public function __construct(
        ShopgateExternalCoupon $extendedExternalCoupon,
        ShopgateOrderItem $extendedOrderItem,
        ShopgateContainerToArrayVisitor $visitor
    ) {
        parent::__construct([]);
        $this->externalCoupon = $extendedExternalCoupon;
        $this->orderItem = $extendedOrderItem;
        $this->visitor = $visitor;
    }

    /**
     * @param ShopgateOrder $order
     * @return $this
     */
    public function loadFromShopgateOrder(ShopgateOrder $order): ExtendedOrder
    {
        $visitor = clone $this->visitor;
        $visitor->visitContainer($order);
        $this->dataToEntity($visitor->getArray());

        return $this;
    }
}
