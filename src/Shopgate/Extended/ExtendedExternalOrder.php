<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use DateTimeInterface;
use Shopgate\Shopware\Customer\Mapping\AddressMapping;
use Shopgate\Shopware\Order\LineItem\LineItemProductMapping;
use Shopgate\Shopware\Order\Taxes\TaxMapping;
use ShopgateAddress;
use ShopgateExternalOrder;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class ExtendedExternalOrder extends ShopgateExternalOrder
{
    private AddressMapping $addressMapping;
    private LineItemProductMapping $productMapping;
    private TaxMapping $taxMapping;

    public function __construct(
        AddressMapping $addressMapping,
        LineItemProductMapping $productMapping,
        TaxMapping $taxMapping
    ) {
        parent::__construct([]);
        $this->addressMapping = $addressMapping;
        $this->productMapping = $productMapping;
        $this->taxMapping = $taxMapping;
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
     * @param OrderLineItemCollection|null $value
     */
    public function setItems($value): void
    {
        /** @var ?ArrayStruct $status */
        $status = $value->getExtension('sg.taxStatus');
        $taxStatus = $status ? $status->getVars()['taxStatus'] : null;
        parent::setItems($value->map(
            fn(OrderLineItemEntity $entity) => $this->productMapping->mapOutgoingOrderProduct($entity, $taxStatus))
        );
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
        parent::setOrderTaxes($value->map(
            fn(CalculatedTax $tax) => $this->taxMapping->mapOutgoingOrderTaxes($tax))
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
