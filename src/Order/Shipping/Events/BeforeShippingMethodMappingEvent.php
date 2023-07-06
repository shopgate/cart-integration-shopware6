<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping\Events;

use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeShippingMethodMappingEvent extends Event
{
    public function __construct(private readonly DeliveryCollection $deliveries)
    {
    }

    public function getDeliveries(): DeliveryCollection
    {
        return $this->deliveries;
    }
}
