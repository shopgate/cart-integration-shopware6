<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Customer\OrderCustomerComposer;
use Shopgate\Shopware\Order\LineItem\LineItemComposer;
use Shopgate\Shopware\Order\Payment\PaymentComposer;
use Shopgate\Shopware\Order\Quote\QuoteBridge;
use Shopgate\Shopware\Order\Shipping\ShippingComposer;
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
    private PaymentComposer $paymentComposer;
    private OrderCustomerComposer $orderCustomerComposer;

    public function __construct(
        ShippingComposer $shippingComposer,
        ContextManager $contextManager,
        ContextComposer $contextComposer,
        LineItemComposer $lineItemComposer,
        QuoteBridge $quoteBridge,
        PaymentComposer $paymentComposer,
        OrderCustomerComposer $orderCustomerComposer
    ) {
        $this->contextManager = $contextManager;
        $this->lineItemComposer = $lineItemComposer;
        $this->quoteBridge = $quoteBridge;
        $this->shippingComposer = $shippingComposer;
        $this->contextComposer = $contextComposer;
        $this->paymentComposer = $paymentComposer;
        $this->orderCustomerComposer = $orderCustomerComposer;
    }

    /**
     * @param ExtendedCart $sgCart
     * @return array
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function checkCart(ExtendedCart $sgCart): array
    {
        $sgCart->invalidateCoupons();
        $customerId = $sgCart->getExternalCustomerId();
        if ($sgCart->isGuest() && $sgCart->getMail()) {
            $customerId = $this->orderCustomerComposer->getOrCreateGuestCustomerByEmail(
                $sgCart->getMail(),
                $sgCart,
                $this->contextManager->getSalesContext()
            )->getId();
        }
        $initContext = $this->contextComposer->getContextByCustomerId($customerId ?? '');
        $cleanCartContext = $this->contextManager->duplicateContextWithNewToken($initContext);
        $this->contextComposer->addCustomerAddress($sgCart, $cleanCartContext);
        $paymentId = $this->paymentComposer->mapIncomingPayment($sgCart, $cleanCartContext);
        $context = $this->contextComposer->addActivePayment($paymentId, $cleanCartContext);
        $shopwareCart = $this->quoteBridge->loadCartFromContext($context);
        $lineItems = $this->lineItemComposer->mapIncomingLineItems($sgCart);
        $updatedCart = $this->lineItemComposer->addLineItemsToCart($shopwareCart, $context, $lineItems);

        $result = [
                'currency' => $context->getCurrency()->getIsoCode(),
                'shipping_methods' => $this->shippingComposer->mapShippingMethods($context),
                'payment_methods' => [],
            ]
            + $this->orderCustomerComposer->mapOutgoingCartCustomer($context)
            + $this->lineItemComposer->mapOutgoingLineItems($updatedCart, $sgCart);

        $this->quoteBridge->deleteCart($context);
        $this->contextManager->resetContext($initContext);

        return $result;
    }
}
