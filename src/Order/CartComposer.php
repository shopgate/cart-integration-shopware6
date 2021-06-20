<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Order\Payment\PaymentComposer;
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
    private PaymentComposer $paymentComposer;

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
        QuoteBridge $quoteBridge,
        PaymentComposer $paymentComposer
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->customerMapping = $customerMapping;
        $this->quoteBridge = $quoteBridge;
        $this->shippingComposer = $shippingComposer;
        $this->contextComposer = $contextComposer;
        $this->paymentComposer = $paymentComposer;
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
        $initContext = $this->contextComposer->getContextByCustomerId($customerId ?? '');
        if (!empty($customerId)) {
            $this->contextComposer->addCustomerAddress($sgCart, $initContext);
        }
        $context = $this->paymentComposer->mapIncomingPayment($sgCart, $initContext);
        $shopwareCart = $this->quoteBridge->loadCartFromContext($context);
        $lineItems = $this->lineItemComposer->mapIncomingLineItems($sgCart);
        $updatedCart = $this->lineItemComposer->addLineItemsToCart($shopwareCart, $context, $lineItems);

        $result = [
                'currency' => $context->getCurrency()->getIsoCode(),
                'shipping_methods' => $this->shippingComposer->mapShippingMethods($context),
                'payment_methods' => [],
                'customer' => $this->customerMapping->mapCartCustomer($context),
            ]
            + $this->lineItemComposer->mapOutgoingLineItems($updatedCart, $sgCart);

        $this->quoteBridge->deleteCart($context);
        $this->contextManager->resetContext();

        return $result;
    }
}
