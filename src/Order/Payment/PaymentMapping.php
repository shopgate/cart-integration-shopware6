<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate\Shopware\System\PaymentHandler\GenericHandler;
use ShopgateCartBase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\CashPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class PaymentMapping
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return string - returns UID of payment method
     */
    public function mapPayment(ShopgateCartBase $sgCart, PaymentMethodCollection $collection): string
    {
        $class = GenericHandler::class;
        switch ($sgCart->getPaymentMethod()) {
            case 'COD':
                $class = CashPayment::class;
                break;
        }

        /** @var ?PaymentMethodEntity $entry */
        $entry = $collection->filterByProperty('handlerIdentifier', $class)->first();
        $this->logger->debug($entry && $entry->getId() !== GenericPayment::UUID
            ? 'Payment method mapping found: ' . $entry->getHandlerIdentifier()
            : 'No mapping found. Defaulting to generic payment method'
        );

        return $entry ? $entry->getId() : GenericPayment::UUID;
    }
}
