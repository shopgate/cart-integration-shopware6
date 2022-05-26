<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote;

use Shopgate\Shopware\Order\Quote\Events\AfterAddLineItemsToQuote;
use Shopgate\Shopware\Order\Quote\Events\AfterCustomerGetOrdersLoadEvent;
use Shopgate\Shopware\Order\Quote\Events\AfterGetOrdersLoadEvent;
use Shopgate\Shopware\Order\Quote\Events\BeforeAddLineItemsToQuote;
use Shopgate\Shopware\Order\Quote\Events\BeforeCustomerGetOrdersLoadEvent;
use Shopgate\Shopware\Order\Quote\Events\BeforeGetOrdersLoadEvent;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\SalesChannel\AbstractOrderRoute;
use Shopware\Core\Checkout\Order\SalesChannel\OrderRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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
    private AbstractOrderRoute $orderRoute;
    private EntityRepositoryInterface $orderRepository;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        AbstractCartOrderRoute $cartOrderRoute,
        AbstractCartLoadRoute $cartLoadRoute,
        AbstractCartItemAddRoute $cartItemAddRoute,
        AbstractCartDeleteRoute $cartDeleteRoute,
        AbstractOrderRoute $orderRoute,
        EntityRepositoryInterface $orderRepository,
        EventDispatcherInterface $dispatcher
    ) {
        $this->cartOrderRoute = $cartOrderRoute;
        $this->cartLoadRoute = $cartLoadRoute;
        $this->cartItemAddRoute = $cartItemAddRoute;
        $this->cartDeleteRoute = $cartDeleteRoute;
        $this->orderRoute = $orderRoute;
        $this->orderRepository = $orderRepository;
        $this->dispatcher = $dispatcher;
    }

    public function loadCartFromContext(SalesChannelContext $context): Cart
    {
        return $this->cartLoadRoute->load(new Request(), $context)->getCart();
    }

    public function addLineItemsToQuote(Request $request, Cart $cart, SalesChannelContext $context): Cart
    {
        $this->dispatcher->dispatch(new BeforeAddLineItemsToQuote($request, $cart, $context));
        $newCart = $this->cartItemAddRoute->add($request, $cart, $context, null)->getCart();
        $this->dispatcher->dispatch(new AfterAddLineItemsToQuote($newCart, $context));

        return $newCart;
    }

    public function createOrder(Cart $cart, SalesChannelContext $context, ?RequestDataBag $data = null): OrderEntity
    {
        return $this->cartOrderRoute->order($cart, $context, $data ?: new RequestDataBag())->getOrder();
    }

    public function updateOrder(string $orderId, array $updateData, SalesChannelContext $context): void
    {
        $updateData['id'] = $orderId;
        $this->orderRepository->update([$updateData], $context->getContext());
    }

    public function getOrdersAsCustomer(
        Request $request,
        Criteria $criteria,
        SalesChannelContext $context
    ): OrderRouteResponse {
        $criteria->setTitle('shopgate::orders::as-customer');
        $this->dispatcher->dispatch(new BeforeCustomerGetOrdersLoadEvent($criteria, $request, $context));
        $result = $this->orderRoute->load($request, $context, $criteria);
        $this->dispatcher->dispatch(new AfterCustomerGetOrdersLoadEvent($result, $context));

        return $result;
    }

    /**
     * @return EntityCollection|OrderCollection
     */
    public function getOrders(Criteria $criteria, SalesChannelContext $context): EntityCollection
    {
        $criteria->setTitle('shopgate::orders');
        $this->dispatcher->dispatch(new BeforeGetOrdersLoadEvent($criteria, $context));
        $result = $this->orderRepository->search($criteria, $context->getContext())->getEntities();
        $this->dispatcher->dispatch(new AfterGetOrdersLoadEvent($result, $context));

        return $result;
    }

    public function deleteCart(SalesChannelContext $context): void
    {
        $this->cartDeleteRoute->delete($context);
    }
}
