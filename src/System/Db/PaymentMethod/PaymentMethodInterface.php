<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\PaymentMethod;

use Shopgate\Shopware\System\Db\ClassCastInterface;

interface PaymentMethodInterface extends ClassCastInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getDescription(): string;

    public function getPaymentHandler(): string;

    public function getPosition(): int;

    /**
     * Whether to allow payment processing after an order is created
     * e.g. cancellations, refund
     */
    public function getAfterOrder(): bool;
}
