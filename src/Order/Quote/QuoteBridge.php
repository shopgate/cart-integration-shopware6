<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote;

use Shopgate\Shopware\Order\Quote\Events\AfterAddLineItemsToQuote;
use Shopgate\Shopware\Order\Quote\Events\BeforeAddLineItemsToQuote;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class QuoteBridge
{
    private AbstractCartOrderRoute $cartOrderRoute;
    private AbstractCartLoadRoute $cartLoadRoute;
    private AbstractCartItemAddRoute $cartItemAddRoute;
    private AbstractCartDeleteRoute $cartDeleteRoute;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        AbstractCartOrderRoute $cartOrderRoute,
        AbstractCartLoadRoute $cartLoadRoute,
        AbstractCartItemAddRoute $cartItemAddRoute,
        AbstractCartDeleteRoute $cartDeleteRoute,
        EventDispatcherInterface $dispatcher
    ) {
        $this->cartOrderRoute = $cartOrderRoute;
        $this->cartLoadRoute = $cartLoadRoute;
        $this->cartItemAddRoute = $cartItemAddRoute;
        $this->cartDeleteRoute = $cartDeleteRoute;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param SalesChannelContext $context
     * @return Cart
     */
    public function loadCartFromContext(SalesChannelContext $context): Cart
    {
        return $this->cartLoadRoute->load(new Request(), $context)->getCart();
    }

    public function addLineItemToQuote(Request $request, Cart $cart, SalesChannelContext $context): Cart
    {
        $this->dispatcher->dispatch(new BeforeAddLineItemsToQuote($request, $cart, $context));
        $newCart = $this->cartItemAddRoute->add($request, $cart, $context, null)->getCart();
        $this->dispatcher->dispatch(new AfterAddLineItemsToQuote($newCart, $context));

        return $newCart;
    }

    /**
     * @param Cart $cart
     * @param SalesChannelContext $context
     * @param RequestDataBag|null $data
     * @return OrderEntity
     */
    public function createOrder(Cart $cart, SalesChannelContext $context, ?RequestDataBag $data = null): OrderEntity
    {
        return $this->cartOrderRoute->order($cart, $context, $data ?: new RequestDataBag())->getOrder();
    }

    /**
     * @param SalesChannelContext $context
     */
    public function deleteCart(SalesChannelContext $context): void
    {
        $this->cartDeleteRoute->delete($context);
    }
}
