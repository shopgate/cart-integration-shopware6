<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

class AfterOutLineItemMappingEvent extends Event
{
    private Cart $swCart;
    private DataBag $dataBag;
    private ExtendedCart $sgCart;

    public function __construct(DataBag $dataBag, Cart $swCart, ExtendedCart $sgCart)
    {
        $this->dataBag = $dataBag;
        $this->sgCart = $sgCart;
        $this->swCart = $swCart;
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
