<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Flysystem;

use ShopgateShippingInfo;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Framework\Uuid\Uuid;

trait ShippingTrait
{
    /**
     * @param string $type - tax class as defined by SW6
     * @return float
     * @see CartPrice::TAX_STATE_GROSS
     * @see CartPrice::TAX_STATE_NET
     * @noinspection UnnecessaryCastingInspection - SDK lies
     * @noinspection PhpCastIsUnnecessaryInspection
     */
    public function getShippingCost(string $type = CartPrice::TAX_STATE_GROSS): float
    {
        return (float)($type === CartPrice::TAX_STATE_GROSS
            ? $this->getShippingInfos()->getAmountGross()
            : $this->getShippingInfos()->getAmountNet());
    }

    public function getShippingId(): ?string
    {
        if (!$this->hasShippingInfo()) {
            return null;
        }

        return $this->getShippingInfos()->getName();
    }

    public function hasShippingInfo(): bool
    {
        return $this->getShippingInfos() instanceof ShopgateShippingInfo;
    }

    /**
     * @param string $type - tax class as defined by SW6
     * @return bool
     */
    public function isShippingFree(string $type = CartPrice::TAX_STATE_GROSS): bool
    {
        return $this->getShippingCost($type) === 0.0;
    }

    public function isShopwareShipping(): bool
    {
        $id = $this->getShippingId();

        return is_string($id) && Uuid::isValid($id);
    }
}