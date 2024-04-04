<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Order\Customer\OrderCustomerComposer;
use Shopgate\Shopware\Order\Events\AfterCheckCartEvent;
use Shopgate\Shopware\Order\Events\BeforeCheckCartEvent;
use Shopgate\Shopware\Order\LineItem\LineItemComposer;
use Shopgate\Shopware\Order\Payment\PaymentComposer;
use Shopgate\Shopware\Order\Quote\QuoteBridge;
use Shopgate\Shopware\Order\Shipping\ShippingComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateLibraryException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CartComposer
{

    public function __construct(
        private readonly ShippingComposer $shippingComposer,
        private readonly ContextManager $contextManager,
        private readonly ContextComposer $contextComposer,
        private readonly LineItemComposer $lineItemComposer,
        private readonly QuoteBridge $quoteBridge,
        private readonly PaymentComposer $paymentComposer,
        private readonly OrderCustomerComposer $orderCustomerComposer,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
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
        // load desktop cart, duplicate its context, add info to context & create new cart based on it
        $initContext = $this->contextComposer->getContextByCustomerId($customerId ?? '');
        $duplicatedContext = $this->contextManager->duplicateContextWithNewToken($initContext, $customerId ?? null);

        $this->eventDispatcher->dispatch(new BeforeCheckCartEvent($sgCart, $duplicatedContext));

        // we cannot rely on context by reference, so we create new context variables
        $cleanCartContext = $this->contextComposer->addCustomerAddress($sgCart, $duplicatedContext);
        $shippingId = $this->shippingComposer->mapIncomingShipping($sgCart, $cleanCartContext);
        $paymentId = $this->paymentComposer->mapIncomingPayment($sgCart, $cleanCartContext);
        $dataBag = new RequestDataBag([
            SalesChannelContextService::SHIPPING_METHOD_ID => $shippingId,
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentId
        ]);
        $context = $this->contextManager->switchContext($dataBag, $cleanCartContext)->getSalesContext();
        $shopwareCart = $this->quoteBridge->loadCartFromContext($context);
        $lineItems = $this->lineItemComposer->mapIncomingLineItems($sgCart);
        $updatedCart = $this->lineItemComposer->addLineItemsToCart($shopwareCart, $context, $lineItems);
        $shippingMethods = $this->shippingComposer->mapOutgoingShipping($context);
        // reset context selected shipping as shipping method export has to mess with it
        $shippingContext = $this->contextComposer->addActiveShipping($shippingId, $context);

        $result = [
                'currency' => $shippingContext->getCurrency()->getIsoCode(),
                'shipping_methods' => $shippingMethods,
                'payment_methods' => $this->paymentComposer->mapOutgoingPayments($shippingContext)
            ]
            + $this->orderCustomerComposer->mapOutgoingCartCustomer($shippingContext)
            + $this->lineItemComposer->mapOutgoingLineItems($updatedCart, $sgCart);

        $result = $this->eventDispatcher->dispatch(new AfterCheckCartEvent($result, $shippingContext))->getResult();

        $this->quoteBridge->deleteCart($shippingContext); // delete newly created cart
        $this->contextComposer->resetContext($initContext, $shippingContext); // revert back to desktop cart

        return $result;
    }
}
