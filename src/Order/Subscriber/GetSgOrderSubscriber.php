<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Order\Quote\Events\BeforeCustomerGetOrdersLoadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GetSgOrderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [BeforeCustomerGetOrdersLoadEvent:: class => 'loginCustomer'];
    }

    public function loginCustomer(BeforeCustomerGetOrdersLoadEvent $event)
    {
    }
}
