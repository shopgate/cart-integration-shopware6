<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Salutations;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ShopgateSalutationEntity extends Entity
{
    use EntityIdTrait;

    public string $shopwareSalutationId;
    /** @var ?string - as defined in the SDK */
    public ?string $value;

    public function setShopwareSalutationId(string $salutationId): ShopgateSalutationEntity
    {
        $this->shopwareSalutationId = $salutationId;

        return $this;
    }

    public function getShopwareSalutationId(): string
    {
        return $this->shopwareSalutationId;
    }

    public function setValue(?string $value): ShopgateSalutationEntity
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
