<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class QuoteBridge
{
    private AbstractCartOrderRoute $cartOrderRoute;
    private AbstractCartLoadRoute $cartLoadRoute;
    private AbstractCartItemAddRoute $cartItemAddRoute;
    private AbstractCartDeleteRoute $cartDeleteRoute;
    private EntityRepositoryInterface $orderRepository;

    public function __construct(
        AbstractCartOrderRoute $cartOrderRoute,
        AbstractCartLoadRoute $cartLoadRoute,
        AbstractCartItemAddRoute $cartItemAddRoute,
        AbstractCartDeleteRoute $cartDeleteRoute,
        EntityRepositoryInterface $orderRepository
    ) {
        $this->cartOrderRoute = $cartOrderRoute;
        $this->cartLoadRoute = $cartLoadRoute;
        $this->cartItemAddRoute = $cartItemAddRoute;
        $this->cartDeleteRoute = $cartDeleteRoute;
        $this->orderRepository = $orderRepository;
    }

    public function loadCartFromContext(SalesChannelContext $context): Cart
    {
        return $this->cartLoadRoute->load(new Request(), $context)->getCart();
    }

    public function addLineItemToQuote(Request $request, Cart $cart, SalesChannelContext $context): Cart
    {
        return $this->cartItemAddRoute->add($request, $cart, $context, null)->getCart();
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

    public function deleteCart(SalesChannelContext $context): void
    {
        $this->cartDeleteRoute->delete($context);
    }
}
