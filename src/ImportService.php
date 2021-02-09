<?php

namespace Shopgate\Shopware;

use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\OrderComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use ShopgateCustomer;
use ShopgateLibraryException;

class ImportService
{
    /** @var CustomerComposer */
    private $customerImport;
    /** @var OrderComposer */
    private $orderImport;

    /**
     * @param CustomerComposer $customerImport
     * @param OrderComposer $orderImport
     */
    public function __construct(CustomerComposer $customerImport, OrderComposer $orderImport)
    {
        $this->customerImport = $customerImport;
        $this->orderImport = $orderImport;
    }

    /**
     * @param string $user
     * @param string $password
     * @param ShopgateCustomer $customer
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    public function registerCustomer(string $user, string $password, ShopgateCustomer $customer): void
    {
        $this->customerImport->registerCustomer($user, $password, $customer);
    }

    /**
     * @param ExtendedCart $cart
     * @return array
     * @throws MissingContextException
     */
    public function checkCart(ExtendedCart $cart): array
    {
        return $this->orderImport->checkCart($cart);
    }
}
