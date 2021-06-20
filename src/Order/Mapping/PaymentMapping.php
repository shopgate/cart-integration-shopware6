<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Mapping;

use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopgate\Shopware\System\PaymentHandler\GenericHandler;
use ShopgateCartBase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\CashPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class PaymentMapping
{
    /**
     * @param ShopgateCartBase $sgCart
     * @param PaymentMethodCollection $collection
     * @return string - returns UID of payment method
     */
    public function mapPayment(ShopgateCartBase $sgCart, PaymentMethodCollection $collection): string
    {
        $class = GenericHandler::class;
        switch ($sgCart->getPaymentMethod()) {
            case 'COD':
                $class = CashPayment::class;
                break;
        }

        /** @var null|PaymentMethodEntity $entry */
        $entry = $collection->filterByProperty('handlerIdentifier', $class)->first();

        return $entry ? $entry->getId() : GenericPayment::UUID;
    }
}
