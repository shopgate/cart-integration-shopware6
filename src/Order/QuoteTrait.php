<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Customer\CustomerComposer;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Order\Mapping\CustomerMapping;
use Shopgate\Shopware\Order\Mapping\QuoteErrorMapping;
use Shopgate\Shopware\Order\Mapping\ShippingMapping;
use Shopgate\Shopware\Shopgate\ShopgateOrderBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCartBase;
use ShopgateLibraryException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;

trait QuoteTrait
{
    // used in this class
    private AddressComposer $addressComposer;
    private ContextManager $contextManager;
    private LineItemComposer $lineItemComposer;
    private QuoteErrorMapping $errorMapping;
    private QuoteBridge $quoteBridge;

    private CustomerMapping $customerMapping;
    private ShippingMapping $shippingMapping;
    private ShippingMethodBridge $shippingBridge;

    private ShopgateOrderBridge $shopgateOrderBridge;
    private CustomerComposer $customerComposer;

    /**
     * @param string $customerNumber
     * @return SalesChannelContext
     * @throws MissingContextException
     */
    private function getContextByCustomer(string $customerNumber): SalesChannelContext
    {
        try {
            return $this->contextManager->loadByCustomerId($customerNumber);
        } catch (Throwable $e) {
            return $this->contextManager->getSalesContext();
        }
    }

    /**
     * Will not do anything if cart is missing customer external ID
     *
     * @param ShopgateCartBase $base
     * @param SalesChannelContext $channel
     * @return SalesChannelContext
     * @throws MissingContextException
     * @throws ShopgateLibraryException
     */
    private function addCustomerAddressToContext(
        ShopgateCartBase $base,
        SalesChannelContext $channel
    ): SalesChannelContext {
        $addressBag = $this->addressComposer->createAddressSwitchData($base, $channel);
        try {
            // making sure that 2 address ID's are different from each other
            if (count(array_unique($addressBag)) === 2) {
                // dirty hack because of some validation bug that causes to keep billing address ID in search criteria
                $this->contextManager->switchContext(
                    new RequestDataBag(
                        [SalesChannelContextService::BILLING_ADDRESS_ID => $addressBag[SalesChannelContextService::BILLING_ADDRESS_ID]]
                    )
                );
                $newContext = $this->contextManager->switchContext(
                    new RequestDataBag(
                        [SalesChannelContextService::SHIPPING_ADDRESS_ID => $addressBag[SalesChannelContextService::SHIPPING_ADDRESS_ID]]
                    )
                );
            } else {
                $newContext = $this->contextManager->switchContext(new RequestDataBag($addressBag));
            }
        } catch (ConstraintViolationException $exception) {
            throw $this->errorMapping->mapConstraintError($exception);
        }

        return $newContext;
    }
}
