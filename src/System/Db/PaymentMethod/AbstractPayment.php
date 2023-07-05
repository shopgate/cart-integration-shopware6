<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\PaymentMethod;

class AbstractPayment implements PaymentMethodInterface
{
    protected string $id;
    protected string $name;
    protected string $description;
    protected string $paymentHandler;
    protected int $position;
    protected bool $afterOrder;
    protected string $availabilityRuleId;

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

    public function getAvailabilityRuleId(): string
    {
        return $this->availabilityRuleId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'position' => $this->position,
            'handlerIdentifier' => $this->paymentHandler,
            'afterOrderEnabled' => $this->afterOrder,
            'availabilityRuleId' => $this->availabilityRuleId
        ];
    }

}
