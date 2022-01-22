<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateContainerToArrayVisitor;
use ShopgateExternalCoupon;
use ShopgateOrder;

class ExtendedOrder extends ShopgateOrder
{
    use CloningTrait;
    use CartUtilityTrait;

    private ShopgateExternalCoupon $externalCoupon;
    private ShopgateContainerToArrayVisitor $visitor;

    public function __construct(
        ShopgateExternalCoupon $extendedExternalCoupon,
        ShopgateContainerToArrayVisitor $visitor
    ) {
        parent::__construct([]);
        $this->externalCoupon = $extendedExternalCoupon;
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

    public function setExternalCoupons($value): void
    {
        if (!is_array($value)) {
            $this->external_coupons = null;
            return;
        }

        foreach ($value as $index => &$element) {
            if ((!is_object($element) || !($element instanceof ShopgateExternalCoupon)) && !is_array($element)) {
                unset($value[$index]);
                continue;
            }

            if (is_array($element)) {
                $class = clone $this->externalCoupon;
                $class->loadArray($element);
                $element = $class;
            }
        }

        // safety
        unset($element);

        $this->external_coupons = $value;
    }
}
