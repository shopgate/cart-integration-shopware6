<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Order\Mapping\QuoteErrorMapping;
use Shopgate\Shopware\Order\Mapping\ShippingMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateLibraryException;

class CartComposer
{
    use QuoteTrait;

    private ShippingComposer $shippingComposer;

    /**
     * @param ShippingMethodBridge $shippingBridge
     * @param ShippingMapping $shippingMapping
     * @param CustomerMapping $customerMapping
     * @param ContextManager $contextManager
     * @param LineItemComposer $lineItemComposer
     * @param QuoteBridge $quoteBridge
     * @param AddressComposer $addressComposer
     * @param QuoteErrorMapping $errorMapping
     */
    public function __construct(
        ShippingComposer $shippingComposer,
        ShippingMethodBridge $shippingBridge,
        ShippingMapping $shippingMapping,
        CustomerMapping $customerMapping,
        ContextManager $contextManager,
        LineItemComposer $lineItemComposer,
        QuoteBridge $quoteBridge,
        AddressComposer $addressComposer,
        QuoteErrorMapping $errorMapping
    ) {
        $this->shippingMapping = $shippingMapping;
        $this->shippingBridge = $shippingBridge;
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->customerMapping = $customerMapping;
        $this->quoteBridge = $quoteBridge;
        $this->addressComposer = $addressComposer;
        $this->errorMapping = $errorMapping;
        $this->shippingComposer = $shippingComposer;
    }

    /**
     * @param ExtendedCart $sgCart
     * @return array
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function checkCart(ExtendedCart $sgCart): array
    {
        $customerId = $sgCart->getExternalCustomerId();
        $context = $this->getContextByCustomer($customerId ?? '');
        if (!empty($customerId)) {
            $this->addCustomerAddressToContext($sgCart, $context);
        }
        $swCart = $this->buildCart($context, $sgCart);
        $items = $this->lineItemComposer->mapOutgoingLineItems($swCart, $sgCart);
        $deliveries = $this->shippingComposer->getCalculatedDeliveries($context);
        $result = [
                'currency' => $context->getCurrency()->getIsoCode(),
                'shipping_methods' => $this->shippingMapping->mapShippingMethods($deliveries),
                'payment_methods' => [],
                'customer' => $this->customerMapping->mapCartCustomer($context),
            ] + $items;

        $this->quoteBridge->deleteCart($context);
        $this->contextManager->resetContext();

        return $result;
    }
}
