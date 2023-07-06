<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote\Events;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeAddLineItemsToQuote extends Event
{
    public function __construct(private readonly Request $request, private readonly Cart $cart, private readonly SalesChannelContext $context)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
