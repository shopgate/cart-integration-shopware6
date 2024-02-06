<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Customer;

use Shopgate\Shopware\Customer\CustomerBridge;
use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateCartBase;
use ShopgateCartCustomer;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Order/Cart endpoint specific customer composer
 */
readonly class OrderCustomerComposer
{
    public function __construct(
        private CustomerBridge $customerBridge,
        private CustomerMapping $customerMapping,
        private CustomerComposer $customerComposer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, ShopgateCartCustomer>
     */
    public function mapOutgoingCartCustomer(SalesChannelContext $context): array
    {
        return ['customer' => $this->customerMapping->mapCartCustomer($context)];
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function getOrCreateGuestCustomerByEmail(
        string $email,
        ShopgateCartBase $cart,
        SalesChannelContext $salesChannelContext
    ): CustomerEntity {
        $guest = $this->customerBridge->getGuestByEmail($email, $salesChannelContext);
        $this->logger->debug($guest ? 'Found existing guest' : 'Creating new guest customer');
        if (null === $guest) {
            $detailCustomer = $this->customerMapping->orderToShopgateCustomer($cart);
            $guest = $this->customerComposer->registerCustomer(null, $detailCustomer);
        }

        return $guest;
    }
}
