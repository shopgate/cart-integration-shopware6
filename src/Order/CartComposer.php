<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateLibraryException;

class CartComposer
{
    private ShippingComposer $shippingComposer;
    private ContextComposer $contextComposer;
    private ContextManager $contextManager;
    private LineItemComposer $lineItemComposer;
    private QuoteBridge $quoteBridge;
    private CustomerMapping $customerMapping;

    /**
     * @param ShippingComposer $shippingComposer
     * @param CustomerMapping $customerMapping
     * @param ContextManager $contextManager
     * @param ContextComposer $contextComposer
     * @param LineItemComposer $lineItemComposer
     * @param QuoteBridge $quoteBridge
     */
    public function __construct(
        ShippingComposer $shippingComposer,
        CustomerMapping $customerMapping,
        ContextManager $contextManager,
        ContextComposer $contextComposer,
        LineItemComposer $lineItemComposer,
        QuoteBridge $quoteBridge
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->customerMapping = $customerMapping;
        $this->quoteBridge = $quoteBridge;
        $this->shippingComposer = $shippingComposer;
        $this->contextComposer = $contextComposer;
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
        $context = $this->contextComposer->getContextByCustomerId($customerId ?? '');
        if (!empty($customerId)) {
            $this->contextComposer->addCustomerAddress($sgCart, $context);
        }
        $shopwareCart = $this->quoteBridge->loadCartFromContext($context);
        $lineItems = $this->lineItemComposer->mapIncomingLineItems($sgCart);
        $updatedCart = $this->lineItemComposer->addLineItemsToCart($shopwareCart, $context, $lineItems);
        $items = $this->lineItemComposer->mapOutgoingLineItems($updatedCart, $sgCart);
        $deliveries = $this->shippingComposer->getCalculatedDeliveries($context);
        $result = [
                'currency' => $context->getCurrency()->getIsoCode(),
                'shipping_methods' => $this->shippingComposer->outgoingShippingMethods($deliveries),
                'payment_methods' => [],
                'customer' => $this->customerMapping->mapCartCustomer($context),
            ]
            + $items;

        $this->quoteBridge->deleteCart($context);
        $this->contextManager->resetContext();

        return $result;
    }
}
