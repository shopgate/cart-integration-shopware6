<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateCartBase;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentComposer
{
    private PaymentBridge $paymentBridge;
    private PaymentMapping $paymentMapping;
    private LoggerInterface $logger;

    public function __construct(PaymentBridge $paymentBridge, PaymentMapping $paymentMapping, LoggerInterface $logger)
    {
        $this->paymentBridge = $paymentBridge;
        $this->paymentMapping = $paymentMapping;
        $this->logger = $logger;
    }

    /**
     * @param ShopgateCartBase $sgCart
     * @param SalesChannelContext $context
     * @return string
     */
    public function mapIncomingPayment(ShopgateCartBase $sgCart, SalesChannelContext $context): string
    {
        $methods = $this->paymentBridge->getAvailableMethods($context);
        $this->logger->debug('Payment methods available to this cart:');
        $this->logger->debug(print_r(array_map(static function (PaymentMethodEntity $entity) {
            return [
                'id' => $entity->getId(),
                'name' => $entity->getName(),
                'handler' => $entity->getHandlerIdentifier()
            ];
        }, $methods->getElements()), true));

        return $this->paymentMapping->mapPayment($sgCart, $methods);
    }
}
