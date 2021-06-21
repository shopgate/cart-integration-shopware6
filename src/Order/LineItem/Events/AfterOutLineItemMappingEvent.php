<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

class AfterOutLineItemMappingEvent extends Event
{
    private DataBag $dataBag;

    /**
     * @param DataBag $dataBag
     */
    public function __construct(DataBag $dataBag)
    {
        $this->dataBag = $dataBag;
    }

    /**
     * @return DataBag
     */
    public function getDataBag(): DataBag
    {
        return $this->dataBag;
    }
}
