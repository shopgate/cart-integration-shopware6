<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCartItem;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalCoupon;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderExtCoupon;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderItem;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrderTax;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Shopgate\Extended\ExtendedProperty;
use Shopgate_Model_Catalog_Property;
use ShopgateCart;
use ShopgateCartItem;
use ShopgateDeliveryNote;
use ShopgateExternalCoupon;
use ShopgateExternalOrderExternalCoupon;
use ShopgateExternalOrderExtraCost;
use ShopgateExternalOrderItem;
use ShopgateExternalOrderTax;
use ShopgateOrder;
use ShopgatePaymentMethod;
use ShopgateShippingMethod;

class ExtendedClassFactory
{
    private ShopgateCart $cart;
    private ShopgateCartItem $cartItem;
    private ShopgateExternalOrderItem $orderItem;
    private ShopgateExternalOrderTax $orderTax;
    private ShopgateExternalCoupon $externalCoupon;
    private ShopgateOrder $order;
    private ShopgateShippingMethod $shippingMethod;
    private ShopgateDeliveryNote $deliveryNote;
    private ShopgateExternalOrderExtraCost $orderExtraCost;
    private ShopgatePaymentMethod $paymentMethod;
    private Shopgate_Model_Catalog_Property $property;
    private ShopgateExternalOrderExternalCoupon $orderExportCoupon;

    public function __construct(
        ShopgateCart $cart,
        ShopgateCartItem $cartItem,
        ShopgateExternalOrderItem $orderItem,
        ShopgateExternalOrderTax $orderTax,
        ShopgateExternalCoupon $externalCoupon,
        ShopgateExternalOrderExternalCoupon $orderExportCoupon,
        ShopgateOrder $order,
        Shopgate_Model_Catalog_Property $property,
        ShopgateShippingMethod $shippingMethod,
        ShopgateDeliveryNote $deliveryNote,
        ShopgateExternalOrderExtraCost $orderExtraCost,
        ShopgatePaymentMethod $paymentMethod
    ) {
        $this->cart = $cart;
        $this->cartItem = $cartItem;
        $this->orderItem = $orderItem;
        $this->orderTax = $orderTax;
        $this->externalCoupon = $externalCoupon;
        $this->orderExportCoupon = $orderExportCoupon;
        $this->order = $order;
        $this->property = $property;
        $this->shippingMethod = $shippingMethod;
        $this->deliveryNote = $deliveryNote;
        $this->orderExtraCost = $orderExtraCost;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return ExtendedCartItem|ShopgateCartItem
     */
    public function createCartItem(): ShopgateCartItem
    {
        return clone $this->cartItem;
    }

    /**
     * @return ExtendedExternalOrderItem|ShopgateExternalOrderItem
     */
    public function createOrderLineItem(): ShopgateExternalOrderItem
    {
        return clone $this->orderItem;
    }

    /**
     * @return ExtendedExternalOrderTax|ShopgateExternalOrderTax
     */
    public function createExternalOrderTax(): ShopgateExternalOrderTax
    {
        return clone $this->orderTax;
    }

    /**
     * @return ExtendedExternalCoupon|ShopgateExternalCoupon
     */
    public function createExternalCoupon(): ShopgateExternalCoupon
    {
        return clone $this->externalCoupon;
    }

    /**
     * @return ExtendedExternalOrderExtCoupon|ShopgateExternalOrderExternalCoupon
     */
    public function createOrderExportCoupon(): ShopgateExternalOrderExternalCoupon
    {
        return clone $this->orderExportCoupon;
    }

    /**
     * @return ExtendedCart|ShopgateCart
     */
    public function createCart(): ShopgateCart
    {
        return clone $this->cart;
    }

    /**
     * @return ExtendedOrder|ShopgateOrder
     */
    public function createOrder(): ShopgateOrder
    {
        return clone $this->order;
    }

    public function createShippingMethod(): ShopgateShippingMethod
    {
        return clone $this->shippingMethod;
    }

    public function createDeliveryNote(): ShopgateDeliveryNote
    {
        return clone $this->deliveryNote;
    }

    public function createOrderExtraCost(): ShopgateExternalOrderExtraCost
    {
        return clone $this->orderExtraCost;
    }

    public function createPaymentMethod(): ShopgatePaymentMethod
    {
        return clone $this->paymentMethod;
    }

    /**
     * @return ExtendedProperty|Shopgate_Model_Catalog_Property
     */
    public function createProperty(): Shopgate_Model_Catalog_Property
    {
        return clone $this->property;
    }
}
