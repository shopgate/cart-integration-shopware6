<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote\Events;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterAddLineItemsToQuote extends Event
{
    private SalesChannelContext $context;
    private Cart $cart;

    public function __construct(Cart $cart, SalesChannelContext $context)
    {

        $this->cart = $cart;
        $this->context = $context;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }
}
