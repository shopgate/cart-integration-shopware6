<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment\Events;

use ShopgateCartBase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Symfony\Contracts\EventDispatcher\Event;

class BeforePaymentTypeMapping extends Event
{

    public function __construct(
        private readonly ShopgateCartBase $sgCart,
        private readonly PaymentMethodCollection $collection
    ) {
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
