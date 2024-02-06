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

readonly class ExtendedClassFactory
{

    public function __construct(
        private ShopgateCart $cart,
        private ShopgateCartItem $cartItem,
        private ShopgateExternalOrderItem $orderItem,
        private ShopgateExternalOrderTax $orderTax,
        private ShopgateExternalCoupon $externalCoupon,
        private ShopgateExternalOrderExternalCoupon $orderExportCoupon,
        private ShopgateOrder $order,
        private Shopgate_Model_Catalog_Property $property,
        private ShopgateShippingMethod $shippingMethod,
        private ShopgateDeliveryNote $deliveryNote,
        private ShopgateExternalOrderExtraCost $orderExtraCost,
        private ShopgatePaymentMethod $paymentMethod
    ) {
    }

    public function createCartItem(): ExtendedCartItem|ShopgateCartItem
    {
        return clone $this->cartItem;
    }

    public function createOrderLineItem(): ExtendedExternalOrderItem|ShopgateExternalOrderItem
    {
        return clone $this->orderItem;
    }

    public function createExternalOrderTax(): ExtendedExternalOrderTax|ShopgateExternalOrderTax
    {
        return clone $this->orderTax;
    }

    public function createExternalCoupon(): ExtendedExternalCoupon|ShopgateExternalCoupon
    {
        return clone $this->externalCoupon;
    }

    public function createOrderExportCoupon(): ExtendedExternalOrderExtCoupon|ShopgateExternalOrderExternalCoupon
    {
        return clone $this->orderExportCoupon;
    }

    public function createCart(): ExtendedCart|ShopgateCart
    {
        return clone $this->cart;
    }

    public function createOrder(): ExtendedOrder|ShopgateOrder
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

    public function createProperty(): ExtendedProperty|Shopgate_Model_Catalog_Property
    {
        return clone $this->property;
    }
}
