<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment\Events;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Symfony\Contracts\EventDispatcher\Event;

class BeforePaymentMethodMapping extends Event
{
    private PaymentMethodEntity $paymentMethod;

    public function __construct(PaymentMethodEntity $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentMethod(): PaymentMethodEntity
    {
        return $this->paymentMethod;
    }
}
