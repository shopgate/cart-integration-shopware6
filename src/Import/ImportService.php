<?php

namespace Shopgate\Shopware\Import;

use Shopgate\Shopware\Exceptions\MissingContextException;
use ShopgateCart;
use ShopgateCustomer;
use ShopgateLibraryException;

class ImportService
{
    /** @var CustomerImport */
    private $customerImport;
    /** @var OrderImport */
    private $orderImport;

    /**
     * @param CustomerImport $customerImport
     * @param OrderImport $orderImport
     */
    public function __construct(CustomerImport $customerImport, OrderImport $orderImport)
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
     * @param ShopgateCart $cart
     * @return array
     */
    public function checkCart(ShopgateCart $cart): array
    {
        return $this->orderImport->checkCart($cart);
    }
}
