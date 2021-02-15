<?php

namespace Shopgate\Shopware\Shopgate\Order;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ShopgateOrderEntity extends Entity
{
    use EntityIdTrait;

    public const NOT_SENT = 0;
    public const STATUS_SENT = 1;
    public const STATUS_SENT_NOT_CANCELLED = 2;

    /** @var string */
    protected $technicalName;

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }
}
