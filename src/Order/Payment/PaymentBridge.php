<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class PaymentBridge
{
    private AbstractPaymentMethodRoute $paymentMethodRoute;

    /**
     * @param AbstractPaymentMethodRoute $paymentMethodRoute
     */
    public function __construct(AbstractPaymentMethodRoute $paymentMethodRoute)
    {
        $this->paymentMethodRoute = $paymentMethodRoute;
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
}
