<?php

namespace Shopgate\Shopware\System\PaymentHandler;

use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPage;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentLoaderSubscriber implements EventSubscriberInterface
{

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AccountPaymentMethodPageLoadedEvent::class => 'excludeShopgatePayment'
        ];
    }

    /**
     * Removes our shopgate generic payment from frontend (customer) rendering
     *
     * @param PageLoadedEvent $event
     */
    public function excludeShopgatePayment(PageLoadedEvent $event): void
    {
        /** @var AccountPaymentMethodPage $page */
        $page = $event->getPage();
        if ($paymentMethods = $page->getSalesChannelPaymentMethods()) {
            $paymentMethods->remove(GenericPayment::UUID);
            $page->setPaymentMethods($paymentMethods);
        }
    }
}
