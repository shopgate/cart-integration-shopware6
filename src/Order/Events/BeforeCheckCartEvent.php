<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeCheckCartEvent extends Event
{
    private SalesChannelContext $context;
    private ExtendedCart $extendedCart;

    /**
     * @param SalesChannelContext $context
     * @param ExtendedCart $extendedCart
     */
    public function __construct(SalesChannelContext $context, ExtendedCart $extendedCart)
    {
        $this->context = $context;
        $this->extendedCart = $extendedCart;
    }

    /**
     * @return SalesChannelContext
     */
    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    /**
     * @return ExtendedCart
     */
    public function getExtendedCart(): ExtendedCart
    {
        return $this->extendedCart;
    }
}
