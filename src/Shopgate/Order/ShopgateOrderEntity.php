<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Order;

use ShopgateOrder;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ShopgateOrderEntity extends Entity
{
    use EntityIdTrait;

    /**
     * Keep public as assigner does not use methods, e.g. $this->$key = value
     */
    public string $shopwareOrderId;
    public string $salesChannelId;
    public string $shopgateOrderNumber;
    public bool $isSent;
    public bool $isCancelled;
    public bool $isPaid;
    public bool $isTest;
    public $receivedData;
    public ?OrderEntity $order = null;

    /**
     * @return string
     */
    public function getShopwareOrderId(): string
    {
        return $this->shopwareOrderId;
    }

    /**
     * @param string $shopwareOrderId
     * @return ShopgateOrderEntity
     */
    public function setShopwareOrderId(string $shopwareOrderId): ShopgateOrderEntity
    {
        $this->shopwareOrderId = $shopwareOrderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     * @return ShopgateOrderEntity
     */
    public function setSalesChannelId(string $salesChannelId): ShopgateOrderEntity
    {
        $this->salesChannelId = $salesChannelId;
        return $this;
    }

    /**
     * @return string
     */
    public function getShopgateOrderNumber(): string
    {
        return $this->shopgateOrderNumber;
    }

    /**
     * @param string $shopgateOrderNumber
     * @return ShopgateOrderEntity
     */
    public function setShopgateOrderNumber(string $shopgateOrderNumber): ShopgateOrderEntity
    {
        $this->shopgateOrderNumber = $shopgateOrderNumber;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsSent(): bool
    {
        return $this->isSent;
    }

    /**
     * @param bool $isSent
     * @return ShopgateOrderEntity
     */
    public function setIsSent(bool $isSent): ShopgateOrderEntity
    {
        $this->isSent = $isSent;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsCancelled(): bool
    {
        return $this->isCancelled;
    }

    /**
     * @param bool $isCancelled
     * @return ShopgateOrderEntity
     */
    public function setIsCancelled(bool $isCancelled): ShopgateOrderEntity
    {
        $this->isCancelled = $isCancelled;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsPaid(): bool
    {
        return $this->isPaid;
    }

    /**
     * @param bool $isPaid
     * @return ShopgateOrderEntity
     */
    public function setIsPaid(bool $isPaid): ShopgateOrderEntity
    {
        $this->isPaid = $isPaid;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsTest(): bool
    {
        return $this->isTest;
    }

    /**
     * @param bool $isTest
     * @return ShopgateOrderEntity
     */
    public function setIsTest(bool $isTest): ShopgateOrderEntity
    {
        $this->isTest = $isTest;
        return $this;
    }

    public function getReceivedData(): ShopgateOrder
    {
        return new ShopgateOrder($this->receivedData);
    }

    /**
     * @param ShopgateOrder $receivedData
     * @return ShopgateOrderEntity
     */
    public function setReceivedData(ShopgateOrder $receivedData): ShopgateOrderEntity
    {
        $this->receivedData = $receivedData->toArray();
        return $this;
    }

    /**
     * @return OrderEntity|null getOrder()
     */
    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    /**
     * @param string $id
     * @param string $channelId
     * @param ShopgateOrder $order
     * @return ShopgateOrderEntity
     */
    public function mapQuote(string $id, string $channelId, ShopgateOrder $order): ShopgateOrderEntity
    {
        return $this
            ->setShopwareOrderId($id)
            ->setShopgateOrderNumber((string)$order->getOrderNumber())
            ->setSalesChannelId($channelId)
            ->setIsTest((bool)$order->getIsTest())
            ->setIsPaid((bool)$order->getIsPaid())
            ->setIsSent(false)
            ->setIsCancelled(false)
            ->setReceivedData($order);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'shopwareOrderId' => $this->shopwareOrderId,
            'salesChannelId' => $this->salesChannelId,
            'shopgateOrderNumber' => $this->shopgateOrderNumber,
            'isSent' => $this->isSent,
            'isCancelled' => $this->isCancelled,
            'isPaid' => $this->isPaid,
            'isTest' => $this->isTest,
            'receivedData' => $this->receivedData,
        ];
    }
}
