<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use ShopgateCartBase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentComposer
{
    private PaymentBridge $paymentBridge;
    private PaymentMapping $paymentMapping;

    public function __construct(PaymentBridge $paymentBridge, PaymentMapping $paymentMapping)
    {
        $this->paymentBridge = $paymentBridge;
        $this->paymentMapping = $paymentMapping;
    }

    /**
     * @param ShopgateCartBase $sgCart
     * @param SalesChannelContext $context
     * @return string
     */
    public function mapIncomingPayment(ShopgateCartBase $sgCart, SalesChannelContext $context): string
    {
        $methods = $this->paymentBridge->getAvailableMethods($context);

        return $this->paymentMapping->mapPayment($sgCart, $methods);
    }
}
