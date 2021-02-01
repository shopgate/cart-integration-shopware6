<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Customer\Mapping\LocationMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCart;
use ShopgateCartBase;
use ShopgateCartCustomer;
use ShopgateCartCustomerGroup;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\CartRuleLoader;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;

class OrderComposer
{
    /** @var LocationMapping */
    private $locationMapping;
    /** @var ContextManager */
    private $contextManager;
    /** @var CartRuleLoader */
    private $cartRuleLoader;
    /** @var CartCalculator */
    private $cartCalculator;

    /**
     * @param LocationMapping $locationMapping
     * @param ContextManager $contextManager
     */
    public function __construct(
        LocationMapping $locationMapping,
        ContextManager $contextManager,
        CartRuleLoader $cartRuleLoader,
        CartCalculator $cartCalculator
    ) {
        $this->locationMapping = $locationMapping;
        $this->contextManager = $contextManager;
        $this->cartRuleLoader = $cartRuleLoader;
        $this->cartCalculator = $cartCalculator;
    }

    /**
     * @param ShopgateCart $cart
     * @return array
     * @throws MissingContextException
     */
    public function checkCart(ShopgateCart $cart): array
    {
        try {
            $this->contextManager->loadByCustomerId($cart->getExternalCustomerId());
        } catch (Throwable $e) {
            // todo-rainer log
        }
        $context = $this->contextManager->getSalesContext();

        $shopwareCart = $this->buildShopwareCart($context, $cart);

        return [
            "currency" => $context->getCurrency()->getIsoCode(),
            "external_coupons" => [], // todo-rainer implement
            "shipping_methods" => [], // todo-rainer implement
            "payment_methods" => [], // out of scope
            "items" => [], // todo-rainer implement
            "customer" => $this->getCartCustomer(),
        ];
    }

    /**
     * @param SalesChannelContext $context
     * @param ShopgateCartBase $cart
     * @return Cart
     */
    protected function buildShopwareCart(SalesChannelContext $context, ShopgateCartBase $cart): Cart
    {
        $shopwareCart = $this->cartRuleLoader->loadByToken($context, $context->getToken())->getCart();

        $lineItems = [];
        foreach ($cart->getItems() as $item) {
            $productId = $item->getItemNumber();
            //todo-rainer manage configurable items
            $lineItems[] = new LineItem($productId, 'product', $productId, $item->getQuantity());
        }

        $shopwareCart->setLineItems(new LineItemCollection($lineItems));

        $shopwareCart = $this->cartCalculator->calculate($shopwareCart, $context);

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
