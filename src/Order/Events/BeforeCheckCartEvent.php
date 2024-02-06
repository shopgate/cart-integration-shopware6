<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeCheckCartEvent extends Event
{
    public function __construct(
        private readonly ExtendedCart $extendedCart,
        private readonly SalesChannelContext $context
    ) {
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
