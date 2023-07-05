<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment\Events;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Symfony\Contracts\EventDispatcher\Event;

class BeforePaymentMethodMapping extends Event
{
    public function __construct(private readonly PaymentMethodEntity $paymentMethod)
    {
    }

    public function getPaymentMethod(): PaymentMethodEntity
    {
        return $this->paymentMethod;
    }
}
