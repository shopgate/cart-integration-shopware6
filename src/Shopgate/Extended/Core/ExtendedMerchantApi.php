<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Core;

use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateAuthenticationServiceInterface;
use ShopgateMerchantApi;
use ShopgateMerchantApiException;
use Throwable;

class ExtendedMerchantApi extends ShopgateMerchantApi
{

    public function __construct(
        ShopgateAuthenticationServiceInterface $authService,
        ?string $shopNumber,
        ?string $apiUrl,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($authService, $shopNumber, $apiUrl);
    }

    public function addOrderDeliveryNote(
        $orderNumber,
        $shippingServiceId,
        $trackingNumber = '',
        $markAsCompleted = false,
        $sendCustomerEmail = false
    ): bool {
        $isSent = true;
        try {
            parent::addOrderDeliveryNote(
                $orderNumber,
                $shippingServiceId,
                $trackingNumber,
                $markAsCompleted,
                $sendCustomerEmail
            );
        } catch (Throwable $e) {
            // set status to "sent" if it's a soft error
            if (!$this->isSoftOrderStatusError($e)) {
                $this->logger->error(
                    "(#$orderNumber) SMA-Error on add delivery note! Message: {$e->getCode()} - {$e->getMessage()}"
                );
                $isSent = false;
            }
        }

        return $isSent;
    }

    public function cancelOrder(
        $orderNumber,
        $cancelCompleteOrder = true,
        $cancellationItems = array(),
        $cancelShipping = false,
        $cancellationNote = ''
    ): bool {
        $isCancelled = true;
        try {
            parent::cancelOrder(
                $orderNumber,
                $cancelCompleteOrder,
                $cancellationItems,
                $cancelShipping,
                $cancellationNote
            );
        } catch (Throwable $e) {
            if (!$this->isSoftOrderStatusError($e)) {
                $this->logger->error(
                    "! (#$orderNumber)  SMA-Error on cancel order! Message: {$e->getCode()} - {$e->getMessage()}"
                );
                $isCancelled = false;
            }
        }

        return $isCancelled;
    }

    private function isSoftOrderStatusError(Throwable $throwable): bool
    {
        return in_array($throwable->getCode(), [
                ShopgateMerchantApiException::ORDER_SHIPPING_STATUS_ALREADY_COMPLETED,
                ShopgateMerchantApiException::ORDER_ALREADY_COMPLETED,
                ShopgateMerchantApiException::ORDER_ALREADY_CANCELLED
            ]
        );
    }
}
