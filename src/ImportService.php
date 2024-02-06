<?php declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Order\OrderComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use ShopgateCustomer;
use ShopgateLibraryException;
use ShopgateOrder;

readonly class ImportService
{
    public function __construct(private CustomerComposer $customerComposer, private OrderComposer $orderComposer)
    {
    }

    /**
     * @throws ShopgateLibraryException
     * @noinspection PhpUnusedParameterInspection
     */
    public function registerCustomer(string $user, string $password, ShopgateCustomer $customer): void
    {
        $this->customerComposer->registerCustomer($password, $customer);
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function addOrder(ExtendedOrder|ShopgateOrder $order): array
    {
        return $this->orderComposer->addOrder($order);
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function updateOrder(ShopgateOrder $order): array
    {
        return $this->orderComposer->updateOrder($order);
    }
}
