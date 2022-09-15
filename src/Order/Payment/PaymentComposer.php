<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use RuntimeException;
use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateCartBase;
use ShopgatePaymentMethod;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

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

    public function mapIncomingPayment(ShopgateCartBase $sgCart, SalesChannelContext $context): string
    {
        $methods = $this->paymentBridge->getAvailableMethods($context);
        $this->logger->debug('Payment methods available to this cart:');
        $this->logger->debug(print_r(array_map(static function (PaymentMethodEntity $entity) {
            return [
                'id' => $entity->getId(),
                'name' => $entity->getTranslation('name') ?: $entity->getName(),
                'handler' => $entity->getHandlerIdentifier()
            ];
        }, $methods->getElements()), true));

        return $this->paymentMapping->mapPaymentType($sgCart, $methods);
    }

    /**
     * @param SalesChannelContext $context
     * @return ShopgatePaymentMethod[]
     */
    public function mapOutgoingPayments(SalesChannelContext $context): array
    {
        return $this->paymentBridge->getAvailableMethods($context)
            ->filter(fn(PaymentMethodEntity $pay) => strpos($pay->getHandlerIdentifier(), 'Shopgate') === false)
            ->map(fn(PaymentMethodEntity $paymentMethod) => $this->paymentMapping->mapPaymentMethod($paymentMethod));
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

    /**
     * Retrieves customer payment method ID from context
     * Defaults to channel if all fallbacks are inactive
     *
     * @param SalesChannelContext $context
     * @return string
     * @throws RuntimeException
     */
    public function getCustomerActivePaymentMethodId(SalesChannelContext $context): string
    {
        $methods = [];
        if ($context->getCustomer() && $context->getCustomer()->getDefaultPaymentMethod()) {
            $methods[$context->getCustomer()->getDefaultPaymentMethod()->getId()] = $context->getCustomer()->getDefaultPaymentMethod()->getId();
        }
        $methods[$context->getSalesChannel()->getPaymentMethodId()] = $context->getSalesChannel()->getPaymentMethodId();
        $criteria = new Criteria($methods);
        $activePayments = $this->paymentBridge->getAvailableMethods($context, $criteria);
        $activePayments->sortByIdArray($methods);
        if (!$payment = $activePayments->first()) {
            throw new RuntimeException('Is SalesChannel default payment method not active?');
        }

        return $payment->getId();
    }

    private function getActualTransaction(?OrderTransactionCollection $transactions): ?OrderTransactionEntity
    {
        return $transactions ? $transactions->last() : null;
    }
}
