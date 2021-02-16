<?php

namespace Shopgate\Shopware\System\Db\PaymentMethod;

class AbstractPayment implements PaymentMethodInterface
{
    protected $id;
    protected $name;
    protected $description;
    protected $paymentHandler;
    protected $position;
    protected $afterOrder;

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPaymentHandler(): string
    {
        return $this->paymentHandler;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getAfterOrder(): bool
    {
        return $this->afterOrder;
    }
}
