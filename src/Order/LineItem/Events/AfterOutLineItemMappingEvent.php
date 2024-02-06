<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

class AfterOutLineItemMappingEvent extends Event
{
    public function __construct(
        private readonly DataBag $dataBag,
        private readonly Cart $swCart,
        private readonly ExtendedCart $sgCart
    ) {
    }

    public function getDataBag(): DataBag
    {
        return $this->dataBag;
    }

    public function getShopwareCart(): Cart
    {
        return $this->swCart;
    }

    public function getShopgateCart(): ExtendedCart
    {
        return $this->sgCart;
    }
}
