<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\ApiCredentials;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ShopgateApiCredentialsEntity extends Entity
{
    use EntityIdTrait;

    public bool $active;
    public string $salesChannelId;
    public string $languageId;
    public int $customerNumber;
    public int $shopNumber;
    public string $apiKey;

    public function setSalesChannelId(string $salesChannelId): ShopgateApiCredentialsEntity
    {
        $this->salesChannelId = $salesChannelId;

        return $this;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setLanguageId(string $languageId): ShopgateApiCredentialsEntity
    {
        $this->languageId = $languageId;

        return $this;
    }

    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    public function setActive(bool $active): ShopgateApiCredentialsEntity
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setCustomerNumber(int $customerNumber): ShopgateApiCredentialsEntity
    {
        $this->customerNumber = $customerNumber;

        return $this;
    }

    public function getCustomerNumber(): int
    {
        return $this->customerNumber;
    }

    public function setShopNumber(int $shopNumber): ShopgateApiCredentialsEntity
    {
        $this->shopNumber = $shopNumber;

        return $this;
    }

    public function getShopNumber(): int
    {
        return $this->shopNumber;
    }

    public function setApiKey(string $apiKey): ShopgateApiCredentialsEntity
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'active' => $this->active,
            'salesChannelId' => $this->salesChannelId,
            'languageId' => $this->languageId,
            'customerNumber' => $this->customerNumber,
            'shopNumber' => $this->shopNumber,
            'apiKey' => $this->apiKey
        ];
    }
}
