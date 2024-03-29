<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate\Shopware\Shopgate\Extended\Flysystem\ShippingTrait;
use ShopgateExternalCoupon;
use ShopgateOrderItem;

/**
 * Common functions for both cart and order objects
 * @method ExtendedExternalCoupon[] getExternalCoupons
 * @method ExtendedOrderItem[] getItems
 */
trait CartUtilityTrait
{
    use ShippingTrait;

    /**
     * @return array<string>
     */
    public function getItemIds(): array
    {
        return array_map(static fn(ShopgateOrderItem $item) => $item->getItemNumber(), $this->items);
    }

    /**
     * Locates item by ID
     *
     * @param string $itemId
     * @return ShopgateOrderItem|null
     */
    public function findItemById(string $itemId): ?ShopgateOrderItem
    {
        $foundItems = array_filter(
            $this->items,
            static function (ShopgateOrderItem $item) use ($itemId) {
                return $item->getItemNumber() === $itemId;
            }
        );
        return $foundItems ? array_pop($foundItems) : null;
    }

    public function findItemByName(string $name): ?ShopgateOrderItem
    {
        $foundItems = array_filter(
            $this->items,
            static function (ShopgateOrderItem $item) use ($name) {
                return $item->getName() === $name;
            }
        );
        return $foundItems ? array_pop($foundItems) : null;
    }

    public function findExternalCoupon(string $code): ?ShopgateExternalCoupon
    {
        $foundItems = array_filter(
            $this->external_coupons,
            static function (ShopgateExternalCoupon $coupon) use ($code) {
                return $coupon->getCode() === $code;
            }
        );
        return $foundItems ? array_pop($foundItems) : null;
    }

    public function findExternalCouponByName(string $name): ?ShopgateExternalCoupon
    {
        $foundItems = array_filter(
            $this->external_coupons,
            static function (ShopgateExternalCoupon $coupon) use ($name) {
                return $coupon->getName() === $name;
            }
        );
        return $foundItems ? array_pop($foundItems) : null;
    }

    /**
     * Checks if order/cart is created by a guest
     * @return bool
     */
    public function isGuest(): bool
    {
        return empty($this->getExternalCustomerId());
    }

    /**
     * @param ExtendedOrderItem[]|null $value
     */
    public function setItems($value): void
    {
        if (!is_array($value)) {
            $this->items = null;

            return;
        }

        foreach ($value as $index => &$element) {
            if ((!is_object($element) || !($element instanceof ShopgateOrderItem)) && !is_array($element)) {
                unset($value[$index]);
                continue;
            }

            if (is_array($element)) {
                $class = clone $this->orderItem;
                $class->loadArray($element);
                $element = $class;
            }
        }
        unset($element);

        $this->items = $value;
    }

    /**
     * @param ExtendedExternalCoupon[]|null $value
     */
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
