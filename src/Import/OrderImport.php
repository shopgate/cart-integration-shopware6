<?php

namespace Shopgate\Shopware\Import;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Export\LocationHelper;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCart;
use ShopgateCartCustomer;
use ShopgateCartCustomerGroup;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;

class OrderImport
{
    /** @var LocationHelper */
    private $locationHelper;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param LocationHelper $locationHelper
     * @param ContextManager $contextManager
     */
    public function __construct(
        LocationHelper $locationHelper,
        ContextManager $contextManager
    ) {
        $this->locationHelper = $locationHelper;
        $this->contextManager = $contextManager;
    }

    /**
     * @param ShopgateCart $cart
     * @return array
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
            "customer" => $this->getCartCustomer($context),
        ];
    }

    /**
     * @param SalesChannelContext $context
     * @return ShopgateCartCustomer
     * @throws MissingContextException
     */
    protected function getCartCustomer(SalesChannelContext $context): ShopgateCartCustomer
    {
        $customerGroupId = $this->contextManager->getSalesContext()->getCurrentCustomerGroup()->getId();
        $sgCustomerGroup = new ShopgateCartCustomerGroup();
        $sgCustomerGroup->setId($customerGroupId);

        $customer = new ShopgateCartCustomer();
        $customer->setCustomerGroups([$sgCustomerGroup]);

        return $customer;
    }
}
