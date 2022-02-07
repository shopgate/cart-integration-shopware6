<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Customer;

use Shopgate\Shopware\Customer\AddressBridge;
use Shopgate\Shopware\Customer\CustomerBridge;
use Shopgate\Shopware\Customer\Mapping\AddressMapping;
use ShopgateAddress;
use ShopgateCartBase;
use ShopgateLibraryException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AddressComposer
{
    private AddressMapping $addressMapping;
    private AddressBridge $addressBridge;
    private CustomerBridge $customerBridge;

    public function __construct(
        CustomerBridge $customerBridge,
        AddressMapping $addressMapping,
        AddressBridge $addressBridge
    ) {
        $this->addressMapping = $addressMapping;
        $this->addressBridge = $addressBridge;
        $this->customerBridge = $customerBridge;
    }

    /**
     * Only for existing customers, map incoming data to existing addresses or create new ones.
     *
     * @param ShopgateCartBase $order
     * @param SalesChannelContext $context
     * @return string[]
     * @throws ShopgateLibraryException
     */
    public function createAddressSwitchData(ShopgateCartBase $order, SalesChannelContext $context): array
    {
        $addressBag = [];
        if ($context->getCustomer()) {
            $deliveryId = $this->getOrCreateAddress($order->getDeliveryAddress(), $context);
            $invoiceId = $this->getOrCreateAddress($order->getInvoiceAddress(), $context);
            $addressBag = [
                SalesChannelContextService::SHIPPING_ADDRESS_ID => $deliveryId,
                SalesChannelContextService::BILLING_ADDRESS_ID => $invoiceId
            ];
        }
        return $addressBag;
    }

    /**
     * Checks existing customer addresses & creates one if necessary.
     * In case we cannot find or create address, shopware order creation will use
     * the default shopware customer addresses. This is why we throw.
     *
     * @throws ShopgateLibraryException
     */
    private function getOrCreateAddress(ShopgateAddress $address, SalesChannelContext $context): ?string
    {
        $customer = $this->customerBridge->getDetailedContextCustomer($context);
        $addressId = $this->addressMapping->getSelectedAddressId($address, $customer);
        if (!$addressId) {
            $shopwareAddress = $this->addressMapping->mapToShopwareAddress($address);
            $addressId = $this->addressBridge->addAddress($shopwareAddress, $context, $customer)->getId();
            // handle customFields separately
            $fields = $shopwareAddress->get('customFields');
            if ($fields instanceof RequestDataBag) {
                $this->addressBridge->updateAddress([['id' => $addressId, 'customFields' => $fields->all()]], $context);
            }
        }
        if (!$addressId) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_NO_ADDRESSES_FOUND,
                var_export($address, true),
                true
            );
        }
        return $addressId;
    }
}
