<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeCheckCartEvent extends Event
{
    private ExtendedCart $extendedCart;
    private SalesChannelContext $context;

    public function __construct(ExtendedCart $extendedCart, SalesChannelContext $context)
    {
        $this->extendedCart = $extendedCart;
        $this->context = $context;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getExtendedCart(): ExtendedCart
    {
        return $this->extendedCart;
    }
}
