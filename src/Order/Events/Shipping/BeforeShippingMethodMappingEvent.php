<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events\Shipping;

use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeShippingMethodMappingEvent extends Event
{
    private DeliveryCollection $deliveries;

    /**
     * @param DeliveryCollection $deliveries
     */
    public function __construct(DeliveryCollection $deliveries)
    {
        $this->deliveries = $deliveries;
    }

    /**
     * @return DataBag
     */
    public function getDeliveries(): DeliveryCollection
    {
        return $this->deliveries;
    }
}
