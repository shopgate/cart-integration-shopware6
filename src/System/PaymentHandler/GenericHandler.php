<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\PaymentHandler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

if (class_exists(AbstractPaymentHandler::class)) {
    class GenericHandler extends AbstractPaymentHandler
    {
        public function supports(
            \Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType $type,
            string $paymentMethodId,
            Context $context
        ): bool {
            return false;
        }

        public function pay(
            Request $request,
            \Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct $transaction,
            Context $context,
            ?Struct $validateStruct
        ): ?RedirectResponse {
            return null;
        }
    }
} else {
    class GenericHandler extends \Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment
    {
    }
}
