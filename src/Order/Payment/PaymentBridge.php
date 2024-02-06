<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use Shopgate\Shopware\Order\State\StateBridge;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Symfony\Component\HttpFoundation\Request;

readonly class PaymentBridge
{

    public function __construct(
        private AbstractPaymentMethodRoute $paymentMethodRoute,
        private StateBridge                $stateBridge,
        private ContextManager             $contextManager
    )
    {
    }

    public function getAvailableMethods(
        SalesChannelContext $context,
        Criteria            $criteria = null
    ): PaymentMethodCollection
    {
        $request = new Request();
        $request->query->set('onlyAvailable', true);

        $criteria = $criteria ?: new Criteria();
        $criteria->setTitle('shopgate::payment-method::available');
        return $this->paymentMethodRoute
            ->load($request, $context, $criteria)
            ->getPaymentMethods();
    }

    public function getAllPaymentMethods(SalesChannelContext $context = null): PaymentMethodCollection
    {
        $channel = $context ?: $this->contextManager->getSalesContext();
        $request = new Request();
        $criteria = new Criteria();
        $criteria->setTitle('shopgate::payment-method::all');
        return $this->paymentMethodRoute
            ->load($request, $channel, $criteria)
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
