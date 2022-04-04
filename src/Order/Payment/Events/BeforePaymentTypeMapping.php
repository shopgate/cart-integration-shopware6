<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment\Events;

use ShopgateCartBase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Symfony\Contracts\EventDispatcher\Event;

class BeforePaymentTypeMapping extends Event
{
    private ShopgateCartBase $sgCart;
    private PaymentMethodCollection $collection;

    public function __construct(ShopgateCartBase $sgCart, PaymentMethodCollection $collection)
    {
        $this->sgCart = $sgCart;
        $this->collection = $collection;
    }

    public function getSgCart(): ShopgateCartBase
    {
        return $this->sgCart;
    }

    public function getCollection(): PaymentMethodCollection
    {
        return $this->collection;
    }
}
