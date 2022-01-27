<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use ShopgateCartBase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class PaymentComposer
{
    private PaymentBridge $paymentBridge;
    private PaymentMapping $paymentMapping;

    public function __construct(PaymentBridge $paymentBridge, PaymentMapping $paymentMapping)
    {
        $this->paymentBridge = $paymentBridge;
        $this->paymentMapping = $paymentMapping;
    }

    public function mapIncomingPayment(ShopgateCartBase $sgCart, SalesChannelContext $context): string
    {
        $methods = $this->paymentBridge->getAvailableMethods($context);

        return $this->paymentMapping->mapPayment($sgCart, $methods);
    }

    public function isPaid(?OrderTransactionCollection $transactions): bool
    {
        return $transactions && $transactions->filterByState(OrderTransactionStates::STATE_PAID)->count() > 0;
    }

    public function setToPaid(
        ?OrderTransactionCollection $transactions,
        SalesChannelContext $context
    ): ?StateMachineStateEntity {
        $transaction = $this->getActualTransaction($transactions);

        return $transaction ? $this->paymentBridge->setOrderToPaid($transaction->getId(), $context) : null;
    }

    private function getActualTransaction(?OrderTransactionCollection $transactions): ?OrderTransactionEntity
    {
        return $transactions ? $transactions->last() : null;
    }
}
