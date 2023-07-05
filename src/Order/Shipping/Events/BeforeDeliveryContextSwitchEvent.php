<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping\Events;

use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeDeliveryContextSwitchEvent extends Event
{
    public function __construct(private readonly DataBag $dataBag)
    {
    }

    public function getDataBag(): DataBag
    {
        return $this->dataBag;
    }
}
