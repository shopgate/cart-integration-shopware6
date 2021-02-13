<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\ShippingMapping;
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
    /** @var LineItemComposer */
    private $lineItemComposer;
    /** @var ShippingMapping */
    private $shippingMapping;

    /**
     * @param ContextManager $contextManager
     * @param CartLoadRoute $cartLoadRoute
     * @param CartItemAddRoute $cartItemAddRoute
     * @param LineItemComposer $lineItemComposer
     * @param ShippingMapping $shippingMapping
     */
    public function __construct(
        ContextManager $contextManager,
        CartLoadRoute $cartLoadRoute,
        CartItemAddRoute $cartItemAddRoute,
        LineItemComposer $lineItemComposer,
        ShippingMapping $shippingMapping
    ) {
        $this->contextManager = $contextManager;
        $this->cartLoadRoute = $cartLoadRoute;
        $this->cartItemAddRoute = $cartItemAddRoute;
        $this->lineItemComposer = $lineItemComposer;
        $this->shippingMapping = $shippingMapping;
    }

    /**
     * @param ExtendedCart $sgCart
     * @return array
     * @throws MissingContextException
     */
    public function checkCart(ExtendedCart $sgCart): array
    {
        try {
            $context = $this->contextManager->loadByCustomerId($sgCart->getExternalCustomerId());
        } catch (Throwable $e) {
            //todo: log, issue with customer therefore load guest cart?
            $context = $this->contextManager->getSalesContext();
        }

        $swCart = $this->checkoutBuilder($context, $sgCart);
        $items = $this->lineItemComposer->mapOutgoingLineItems($swCart, $sgCart);

        return [
                'currency' => $context->getCurrency()->getIsoCode(),
                'shipping_methods' => $this->shippingMapping->mapShippingMethods(),
                'payment_methods' => [], // out of scope
                'customer' => $this->getCartCustomer(),
            ] + $items;
    }

    /**
     * @param SalesChannelContext $context
     * @param ShopgateCartBase $cart
     * @return Cart
     */
    protected function checkoutBuilder(SalesChannelContext $context, ShopgateCartBase $cart): Cart
    {
        $shopwareCart = $this->cartLoadRoute->load(new Request(), $context)->getCart();
        $lineItems = $this->lineItemComposer->mapIncomingLineItems($cart);
        $request = new Request();
        $request->request->set('items', $lineItems);
        $this->cartItemAddRoute->add($request, $shopwareCart, $context, null);

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
