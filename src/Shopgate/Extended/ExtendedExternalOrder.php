<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use DateTimeInterface;
use Shopgate\Shopware\Customer\Mapping\AddressMapping;
use Shopgate\Shopware\Order\LineItem\LineItemProductMapping;
use Shopgate\Shopware\Order\LineItem\LineItemPromoMapping;
use Shopgate\Shopware\Order\Shipping\ShippingMapping;
use Shopgate\Shopware\Order\Taxes\TaxMapping;
use ShopgateAddress;
use ShopgateExternalOrder;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class ExtendedExternalOrder extends ShopgateExternalOrder
{
    private AddressMapping $addressMapping;
    private LineItemProductMapping $productMapping;
    private TaxMapping $taxMapping;
    private LineItemPromoMapping $promoMapping;
    private ShippingMapping $shippingMapping;

    public function __construct(
        AddressMapping $addressMapping,
        LineItemProductMapping $productMapping,
        LineItemPromoMapping $promoMapping,
        TaxMapping $taxMapping,
        ShippingMapping $shippingMapping
    ) {
        parent::__construct([]);
        $this->addressMapping = $addressMapping;
        $this->productMapping = $productMapping;
        $this->promoMapping = $promoMapping;
        $this->taxMapping = $taxMapping;
        $this->shippingMapping = $shippingMapping;
    }

    /**
     * @param DateTimeInterface $value
     */
    public function setCreatedTime($value): void
    {
        parent::setCreatedTime($value->format(DateTimeInterface::ATOM));
    }

    /**
     * @param StateMachineStateEntity|null $value
     */
    public function setStatusName($value): void
    {
        parent::setStatusName($value ? $value->getName() : null);
    }

    /**
     * @param CurrencyEntity|null $value
     */
    public function setCurrency($value): void
    {
        parent::setCurrency($value ? $value->getIsoCode() : null);
    }

    /**
     * @param OrderEntity $value
     */
    public function setItems($value): void
    {
        if (null === ($lineItems = $value->getLineItems())) {
            return;
        }
        $status = $value->getTaxStatus();
        parent::setItems(
            $lineItems
                ->filterByType(LineItem::PRODUCT_LINE_ITEM_TYPE)
                ->map(fn(OrderLineItemEntity $item) => $this->productMapping->mapOutgoingOrderProduct($item, $status)));
    }

    /**
     * @param OrderCustomerEntity|null $value
     */
    public function setMail($value): void
    {
        parent::setMail($value ? $value->getEmail() : null);
    }

    public function setShippingAddress(?OrderAddressEntity $value, string $billingId): void
    {
        if (null === $value) {
            return;
        }
        $this->setDeliveryAddress($this->mapAddress($value, $billingId, $value->getId()));
    }

    public function setBillingAddress(?OrderAddressEntity $value, string $shippingId): void
    {
        if (null === $value) {
            return;
        }
        $this->setInvoiceAddress($this->mapAddress($value, $value->getId(), $shippingId));
    }

    /**
     * @param OrderAddressEntity|null $value
     */
    public function setPhone($value): void
    {
        parent::setPhone($value ? $value->getPhoneNumber() : null);
    }

    /**
     * @param CalculatedTaxCollection $value
     */
    public function setOrderTaxes($value): void
    {
        parent::setOrderTaxes($value->map(fn(CalculatedTax $tax) => $this->taxMapping->mapOutgoingOrderTaxes($tax)));
    }

    /**
     * Deliveries are presorted. The first element is the real shipping cost.
     * The rest are discounts on shipping cost.
     * Line items will contain 0 priced promotions (these are artifacts of discounted shipping costs)
     * We get rid of the line item 0 promotion items and add them from the delivery list.
     *
     * @param OrderEntity $value
     */
    public function setExternalCoupons($value): void
    {
        $promos = [];
        $status = $value->getTaxStatus();
        if ($value->getLineItems()) {
            $promos = $value->getLineItems()
                ->filterByType(LineItem::PROMOTION_LINE_ITEM_TYPE)
                ->filter(fn(OrderLineItemEntity $entity) => abs($entity->getTotalPrice()) !== 0.0)
                ->map(fn(OrderLineItemEntity $entity) => $this->promoMapping->mapOutgoingOrderPromo($entity, $status));
        }
        parent::setExternalCoupons(array_merge(
            $promos,
            $value->getDeliveries() ? $value->getDeliveries()->slice(1)->map(
                fn(OrderDeliveryEntity $entity) => $this->promoMapping->mapOutgoingOrderShippingPromo($entity, $status)
            ) : []
        ));
    }

    /**
     * Deliveries are presorted. The first element is the real shipping cost.
     *
     * @param OrderDeliveryCollection $value
     */
    public function setDeliveryNotes($value): void
    {
        // everything past the first note is a discounted shipping (coupon or promo)
        parent::setDeliveryNotes($value->slice(0, 1)->map(
            fn(OrderDeliveryEntity $entity) => $this->shippingMapping->mapOutgoingOrderDeliveryNote($entity))
        );
    }

    /**
     * @param OrderEntity $value
     */
    public function setExtraCosts($value): void
    {
        parent::setExtraCosts(
            array_merge(
                $value->getDeliveries() ? $value->getDeliveries()->slice(0, 1)->map(
                    fn(OrderDeliveryEntity $entity) => $this->shippingMapping->mapOutOrderShippingMethod($entity)
                ) : [])
        );
    }

    private function mapAddress(
        OrderAddressEntity $addressEntity,
        string $billingId,
        string $shippingId
    ): ShopgateAddress {
        $type = $this->addressMapping->mapAddressType($addressEntity, $billingId, $shippingId);

        return $this->addressMapping->mapAddress($addressEntity, $type);
    }
}
