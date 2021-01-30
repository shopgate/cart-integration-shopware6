<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Customer\Mapping\LocationMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCart;
use ShopgateCartCustomer;
use ShopgateCartCustomerGroup;
use Throwable;

class OrderComposer
{
    /** @var LocationMapping */
    private $locationMapping;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param LocationMapping $locationMapping
     * @param ContextManager $contextManager
     */
    public function __construct(
        LocationMapping $locationMapping,
        ContextManager $contextManager
    ) {
        $this->locationMapping = $locationMapping;
        $this->contextManager = $contextManager;
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

        // todo-rainer implement

        return [
            "currency" => $context->getCurrency()->getIsoCode(),
            "external_coupons" => [],
            "shipping_methods" => [],
            "payment_methods" => [],
            "items" => [],
            "customer" => $this->getCartCustomer(),
        ];
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
