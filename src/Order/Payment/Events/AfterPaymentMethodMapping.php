<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment\Events;

use ShopgatePaymentMethod;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class AfterPaymentMethodMapping
{
    private ShopgatePaymentMethod $method;
    private PaymentMethodEntity $paymentMethod;

    public function __construct(PaymentMethodEntity $paymentMethod, ShopgatePaymentMethod $method)
    {
        $this->method = $method;
        $this->paymentMethod = $paymentMethod;
    }

    public function getMethod(): ShopgatePaymentMethod
    {
        return $this->method;
    }

    public function getPaymentMethod(): PaymentMethodEntity
    {
        return $this->paymentMethod;
    }
}
