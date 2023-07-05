<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Order\Customer\AddressComposer;
use Shopgate\Shopware\Order\Payment\PaymentComposer;
use Shopgate\Shopware\Order\Quote\QuoteErrorMapping;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCartBase;
use ShopgateLibraryException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;

class ContextComposer
{

    public function __construct(
        private readonly ContextManager    $contextManager,
        private readonly AddressComposer   $addressComposer,
        private readonly QuoteErrorMapping $errorMapping,
        private readonly PaymentComposer   $paymentComposer
    )
    {
    }

    public function getContextByCustomerId(string $customerId): SalesChannelContext
    {
        try {
            return $this->contextManager->loadByCustomerId($customerId)->getSalesContext();
        } catch (Throwable $e) {
            return $this->contextManager->getSalesContext();
        }
    }

    /**
     * Will not do anything if cart is missing customer external ID
     *
     * @throws ShopgateLibraryException
     */
    public function addCustomerAddress(ShopgateCartBase $base, SalesChannelContext $channel): SalesChannelContext
    {
        $addressBag = $this->addressComposer->createAddressSwitchData($base, $channel);
        try {
            // making sure that 2 address IDs are different from each other
            if (count(array_unique($addressBag)) === 2) {
                // dirty hack because of some validations bug that causes to keep billing address ID in search criteria
                $this->contextManager->switchContext(
                    new RequestDataBag(
                        [SalesChannelContextService::BILLING_ADDRESS_ID => $addressBag[SalesChannelContextService::BILLING_ADDRESS_ID]]
                    ),
                    $channel
                );
                $newContext = $this->contextManager->switchContext(
                    new RequestDataBag(
                        [SalesChannelContextService::SHIPPING_ADDRESS_ID => $addressBag[SalesChannelContextService::SHIPPING_ADDRESS_ID]]
                    ),
                    $channel
                )->getSalesContext();
            } else {
                $newContext = $this->contextManager->switchContext(new RequestDataBag($addressBag), $channel)->getSalesContext();
            }
        } catch (ConstraintViolationException $exception) {
            throw $this->errorMapping->mapConstraintError($exception);
        }

        return $newContext;
    }

    public function addActivePayment(string $uid, SalesChannelContext $context): SalesChannelContext
    {
        $dataBag = [SalesChannelContextService::PAYMENT_METHOD_ID => $uid];

        return $this->contextManager->switchContext(new RequestDataBag($dataBag), $context)->getSalesContext();
    }

    public function addActiveShipping(string $shippingId, SalesChannelContext $context): SalesChannelContext
    {
        $dataBag = [SalesChannelContextService::SHIPPING_METHOD_ID => $shippingId];

        return $this->contextManager->switchContext(new RequestDataBag($dataBag), $context)->getSalesContext();
    }

    public function resetContext(
        SalesChannelContext $originalContext,
        SalesChannelContext $currentContext
    ): SalesChannelContext
    {
        $payment = $this->paymentComposer->getCustomerActivePaymentMethodId($currentContext);
        $shipping = $currentContext->getSalesChannel()->getShippingMethodId();

        return $this->contextManager->switchContext(
            new RequestDataBag([
                SalesChannelContextService::PAYMENT_METHOD_ID => $payment,
                SalesChannelContextService::SHIPPING_METHOD_ID => $shipping
            ]), $originalContext)->getSalesContext();
    }
}
