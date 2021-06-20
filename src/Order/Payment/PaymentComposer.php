<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use Shopgate\Shopware\Order\ContextComposer;
use ShopgateCartBase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PaymentComposer
{
    private ContextComposer $contextComposer;
    private PaymentBridge $paymentBridge;
    private PaymentMapping $paymentMapping;

    /**
     * @param ContextComposer $contextComposer
     * @param PaymentBridge $paymentBridge
     * @param PaymentMapping $paymentMapping
     */
    public function __construct(
        ContextComposer $contextComposer,
        PaymentBridge $paymentBridge,
        PaymentMapping $paymentMapping
    ) {
        $this->contextComposer = $contextComposer;
        $this->paymentBridge = $paymentBridge;
        $this->paymentMapping = $paymentMapping;
    }

    /**
     * @param ShopgateCartBase $sgCart
     * @param SalesChannelContext $context
     * @return SalesChannelContext
     */
    public function mapIncomingPayment(ShopgateCartBase $sgCart, SalesChannelContext $context): SalesChannelContext
    {
        $methods = $this->paymentBridge->getAvailableMethods($context);
        $paymentUid = $this->paymentMapping->mapPayment($sgCart, $methods);

        return $this->contextComposer->addActivePayment($paymentUid, $context);
    }
}
