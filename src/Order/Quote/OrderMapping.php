<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote;

use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalOrder;
use Shopgate\Shopware\Shopgate\NativeOrderExtension;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderEntity;
use Shopgate\Shopware\System\CustomFields\CustomFieldMapping;
use ShopgateContainer;
use ShopgateOrder;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;

class OrderMapping
{
    private CustomFieldMapping $customFieldMapping;
    /** @var ExtendedExternalOrder */
    private ShopgateContainer $sgExternalOrder;

    public function __construct(CustomFieldMapping $customFieldMapping, ShopgateContainer $sgExternalOrder)
    {
        $this->customFieldMapping = $customFieldMapping;
        $this->sgExternalOrder = $sgExternalOrder;
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

        $sgOrder->setCreatedTime($swOrder->getOrderDateTime());
        $sgOrder->setExternalOrderId($swOrder->getId());
        $sgOrder->setExternalOrderNumber($swOrder->getOrderNumber());
        $sgOrder->setStatusName($swOrder->getStateMachineState());
        $sgOrder->setAmountCompleteNet($swOrder->getAmountNet());
        $sgOrder->setAmountCompleteGross($swOrder->getPrice()->getTotalPrice());
        $sgOrder->setAmountItemsGross($swOrder->getPositionPrice());
        $sgOrder->setCurrency($swOrder->getCurrency());
        $sgOrder->setCustomFields($this->customFieldMapping->mapToShopgateCustomFields($swOrder));
        if ($lineItems = $swOrder->getLineItems()) {
            $lineItems->addExtension(
                'sg.taxStatus',
                (new class() extends Struct {
                })->assign(['taxStatus' => $swOrder->getTaxStatus()])
            );
            $sgOrder->setItems($lineItems);
            $lineItems->removeExtension('sg.taxStatus');
        }
        $sgOrder->setOrderTaxes($swOrder->getPrice()->getCalculatedTaxes());

        // customer
        $sgOrder->setMail($swOrder->getOrderCustomer());
        $billingId = $shippingId = $swOrder->getBillingAddressId();
        if ($swOrder->getDeliveries() && ($shipping = $swOrder->getDeliveries()->getShippingAddress()->first())) {
            $shippingId = $shipping->getId(); // this setter is  important
            $sgOrder->setShippingAddress($shipping, $billingId);
        }
        $sgOrder->setBillingAddress($swOrder->getBillingAddress(), $shippingId);
        $sgOrder->setPhone($swOrder->getBillingAddress());

        return $sgOrder;
    }
}
