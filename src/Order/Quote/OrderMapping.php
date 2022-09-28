<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote;

use Shopgate\Shopware\Order\Shipping\ShippingComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrder;
use Shopgate\Shopware\Shopgate\NativeOrderExtension;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\System\CustomFields\CustomFieldMapping;
use ShopgateContainer;
use ShopgateOrder;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\OrderEntity;

class OrderMapping
{
    private CustomFieldMapping $customFieldMapping;
    /** @var ExtendedExternalOrder */
    private ShopgateContainer $sgExternalOrder;
    private ShippingComposer $shippingComposer;

    public function __construct(
        CustomFieldMapping $customFieldMapping,
        ShopgateContainer $sgExternalOrder,
        ShippingComposer $shippingComposer
    ) {
        $this->customFieldMapping = $customFieldMapping;
        $this->sgExternalOrder = $sgExternalOrder;
        $this->shippingComposer = $shippingComposer;
    }

    public function mapIncomingOrder(ShopgateOrder $shopgateOrder): array
    {
        return $this->customFieldMapping->mapToShopwareCustomFields($shopgateOrder);
    }

    public function mapOutgoingOrder(OrderEntity $swOrder): ShopgateContainer
    {
        $sgOrder = clone $this->sgExternalOrder;
        /** @var ?ShopgateOrderEntity $extension */
        if ($extension = $swOrder->getExtension(NativeOrderExtension::PROPERTY)) {
            $sgOrder->setOrderNumber($extension->getShopgateOrderNumber());
            $sgOrder->setIsPaid($extension->getIsPaid());
            // setting the original payment method as we have no mapping yet
            $originalOrder = $extension->getReceivedData();
            $sgOrder->setPaymentMethod($originalOrder->getPaymentInfos()['shopgate_payment_name'] ?? $originalOrder->getPaymentMethod());
            $sgOrder->setPaymentTransactionNumber($originalOrder->getPaymentTransactionNumber());
            $sgOrder->setPaymentTime($originalOrder->getPaymentTime());
        }
        // sort deliveries to contain shipping cost as first item & the rest discounts
        $this->shippingComposer->sortOrderDeliveries($swOrder->getDeliveries());
        $sgOrder->setCreatedTime($swOrder->getOrderDateTime());
        $sgOrder->setExternalOrderId($swOrder->getId());
        $sgOrder->setExternalOrderNumber($swOrder->getOrderNumber());
        $sgOrder->setStatusName($swOrder->getStateMachineState());
        $sgOrder->setAmountCompleteNet($swOrder->getAmountNet());
        $sgOrder->setAmountCompleteGross($swOrder->getPrice()->getTotalPrice());
        if ($swOrder->getTaxStatus() === CartPrice::TAX_STATE_GROSS) {
            $sgOrder->setAmountItemsGross($swOrder->getPositionPrice());
        } else {
            $sgOrder->setAmountItemsNet($swOrder->getPositionPrice());
        }
        $sgOrder->setCurrency($swOrder->getCurrency());
        $sgOrder->setCustomFields($this->customFieldMapping->mapToShopgateCustomFields($swOrder));
        $sgOrder->setItems($swOrder);
        $sgOrder->setExternalCoupons($swOrder);
        $sgOrder->setOrderTaxes($swOrder->getPrice()->getCalculatedTaxes());

        // customer
        $sgOrder->setMail($swOrder->getOrderCustomer());
        $billingId = $shippingId = $swOrder->getBillingAddressId();

        // as you can see, we are not accounting for multi-address shipping here
        if ($swOrder->getDeliveries() && ($shipping = $swOrder->getDeliveries()->getShippingAddress()->first())) {
            $shippingId = $shipping->getId(); // this setter is  important
            $sgOrder->setShippingAddress($shipping, $billingId);
        }
        $sgOrder->setBillingAddress($swOrder->getBillingAddress(), $shippingId);
        $sgOrder->setPhone($swOrder->getBillingAddress());

        // shipping
        $sgOrder->setDeliveryNotes($swOrder->getDeliveries());
        $sgOrder->setExtraCosts($swOrder);

        return $sgOrder;
    }
}
