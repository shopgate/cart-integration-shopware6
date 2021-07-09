<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Customer;

use Shopgate\Shopware\Customer\CustomerBridge;
use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use ShopgateCartBase;
use ShopgateCartCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Order/Cart endpoint specific customer composer
 */
class OrderCustomerComposer
{

    private CustomerBridge $customerBridge;
    private CustomerMapping $customerMapping;
    private CustomerComposer $customerComposer;

    public function __construct(
        CustomerBridge $customerBridge,
        CustomerMapping $customerMapping,
        CustomerComposer $customerComposer
    ) {
        $this->customerBridge = $customerBridge;
        $this->customerMapping = $customerMapping;
        $this->customerComposer = $customerComposer;
    }

    /**
     * @param SalesChannelContext $context
     * @return array<string, ShopgateCartCustomer>
     */
    public function mapOutgoingCartCustomer(SalesChannelContext $context): array
    {
        return ['customer' => $this->customerMapping->mapCartCustomer($context)];
    }

    /**
     * @param ShopgateCartBase $cart
     * @param SalesChannelContext $salesChannelContext
     * @return CustomerEntity
     * @throws ShopgateLibraryException
     * @throws MissingContextException
     */
    public function getOrCreateGuestCustomer(
        ShopgateCartBase $cart,
        SalesChannelContext $salesChannelContext
    ): CustomerEntity {
        $guest = $this->customerBridge->getGuestByEmail($cart->getMail(), $salesChannelContext);
        if (null === $guest) {
            $detailCustomer = $this->customerMapping->orderToShopgateCustomer($cart);
            $guest = $this->customerComposer->registerCustomer(null, $detailCustomer);
        }

        return $guest;
    }
}
