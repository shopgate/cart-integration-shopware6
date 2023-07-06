<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment\Events;

use ShopgatePaymentMethod;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class AfterPaymentMethodMapping
{
    public function __construct(private readonly PaymentMethodEntity $paymentMethod, private readonly ShopgatePaymentMethod $method)
    {
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
