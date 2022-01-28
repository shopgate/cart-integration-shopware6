<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use Shopgate\Shopware\Order\State\StateBridge;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Symfony\Component\HttpFoundation\Request;

class PaymentBridge
{
    private AbstractPaymentMethodRoute $paymentMethodRoute;
    private StateBridge $stateBridge;

    public function __construct(AbstractPaymentMethodRoute $paymentMethodRoute, StateBridge $stateBridge)
    {
        $this->paymentMethodRoute = $paymentMethodRoute;
        $this->stateBridge = $stateBridge;
    }

    /**
     * @param SalesChannelContext $context
     * @return PaymentMethodCollection
     */
    public function getAvailableMethods(SalesChannelContext $context): PaymentMethodCollection
    {
        $request = new Request();
        $request->query->set('onlyAvailable', true);

        return $this->paymentMethodRoute
            ->load($request, $context, new Criteria())
            ->getPaymentMethods();
    }

    public function setOrderToPaid(string $transactionId, SalesChannelContext $context): ?StateMachineStateEntity
    {
        return $this->stateBridge->transition(
            'order_transaction',
            $transactionId,
            'pay',
            $context->getContext());
    }
}
