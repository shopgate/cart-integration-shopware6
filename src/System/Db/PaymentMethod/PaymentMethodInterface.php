<?php

namespace Shopgate\Shopware\System\Db\PaymentMethod;

interface PaymentMethodInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getDescription(): string;

    public function getPaymentHandler(): string;

    public function getPosition(): int;

    /**
     * Whether to allow payment processing after an order is created
     * e.g. cancellations, refund
     * @return bool
     */
    public function getAfterOrder(): bool;
}
