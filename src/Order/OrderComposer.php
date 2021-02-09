<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\LineItemMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCartBase;
use ShopgateCartCustomer;
use ShopgateCartCustomerGroup;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartLoadRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class OrderComposer
{
    /** @var ContextManager */
    private $contextManager;
    /** @var CartLoadRoute */
    private $cartLoadRoute;
    /** @var CartItemAddRoute */
    private $cartItemAddRoute;
    /** @var LineItemMapping */
    private $lineItemMapping;

    /**
     * @param ContextManager $contextManager
     * @param CartLoadRoute $cartLoadRoute
     * @param CartItemAddRoute $cartItemAddRoute
     * @param LineItemMapping $lineItemMapping
     */
    public function __construct(
        ContextManager $contextManager,
        CartLoadRoute $cartLoadRoute,
        CartItemAddRoute $cartItemAddRoute,
        LineItemMapping $lineItemMapping
    ) {
        $this->contextManager = $contextManager;
        $this->cartLoadRoute = $cartLoadRoute;
        $this->cartItemAddRoute = $cartItemAddRoute;
        $this->lineItemMapping = $lineItemMapping;
    }

    /**
     * @param ExtendedCart $cart
     * @return array
     * @throws MissingContextException
     */
    public function checkCart(ExtendedCart $cart): array
    {
        try {
            $this->contextManager->loadByCustomerId($cart->getExternalCustomerId());
        } catch (Throwable $e) {
            // todo-rainer log
        }
        $context = $this->contextManager->getSalesContext();

        $shopwareCart = $this->buildShopwareCart($context, $cart);
        $items = $this->lineItemMapping->mapOutgoingLineItems($shopwareCart, $cart);

        return [
            'currency' => $context->getCurrency()->getIsoCode(),
            'external_coupons' => [], // todo-rainer implement
            'shipping_methods' => [], // todo-rainer implement
            'payment_methods' => [], // out of scope
            'items' => $items,
            'customer' => $this->getCartCustomer(),
        ];
    }

    /**
     * @param SalesChannelContext $context
     * @param ShopgateCartBase $cart
     * @return Cart
     */
    protected function buildShopwareCart(SalesChannelContext $context, ShopgateCartBase $cart): Cart
    {
        $shopwareCart = $this->cartLoadRoute->load(new Request(), $context)->getCart();
        $lineItems = $this->lineItemMapping->mapIncomingLineItems($cart);
        $request = new Request();
        $request->request->set('items', $lineItems);
        $shopwareCart = $this->cartItemAddRoute->add($request, $shopwareCart, $context, null)->getCart();

        return $shopwareCart;
    }

    /**
     * @return ShopgateCartCustomer
     * @throws MissingContextException
     */
    protected function getCartCustomer(): ShopgateCartCustomer
    {
        $customerGroupId = $this->contextManager->getSalesContext()->getCurrentCustomerGroup()->getId();
        $sgCustomerGroup = new ShopgateCartCustomerGroup();
        $sgCustomerGroup->setId($customerGroupId);

        $customer = new ShopgateCartCustomer();
        $customer->setCustomerGroups([$sgCustomerGroup]);

        return $customer;
    }
}
