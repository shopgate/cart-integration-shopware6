<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment\Events;

use ShopgatePaymentMethod;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

readonly class AfterPaymentMethodMapping
{
    public function __construct(private PaymentMethodEntity $paymentMethod, private ShopgatePaymentMethod $method)
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
