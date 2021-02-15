<?php

namespace Shopgate\Shopware;

use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use ShopgateCustomer;
use ShopgateLibraryException;

class ImportService
{
    /** @var CustomerComposer */
    private $customerImport;

    /**
     * @param CustomerComposer $customerImport
     */
    public function __construct(CustomerComposer $customerImport)
    {
        $this->customerImport = $customerImport;
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
}
