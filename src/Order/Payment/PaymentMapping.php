<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Payment;

use Shopgate\Shopware\Order\Payment\Events\AfterPaymentMethodMapping;
use Shopgate\Shopware\Order\Payment\Events\BeforePaymentMethodMapping;
use Shopgate\Shopware\Order\Payment\Events\BeforePaymentTypeMapping;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopgate\Shopware\System\Log\LoggerInterface;
use Shopgate\Shopware\System\PaymentHandler\GenericHandler;
use ShopgateCartBase;
use ShopgatePaymentMethod;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\CashPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class PaymentMapping
{

    public function __construct(
        private LoggerInterface $logger,
        private ExtendedClassFactory $classFactory,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @return string - returns UID of payment method
     */
    public function mapPaymentType(ShopgateCartBase $sgCart, PaymentMethodCollection $collection): string
    {
        $this->dispatcher->dispatch(new BeforePaymentTypeMapping($sgCart, $collection));
        $class = GenericHandler::class;
        if ($sgCart->getPaymentMethod() == 'COD') {
            $class = CashPayment::class;
        }

        /** @var ?PaymentMethodEntity $entry */
        $entry = $collection->filterByProperty('handlerIdentifier', $class)->first();
        $this->logger->debug(
            $entry && $entry->getId() !== GenericPayment::UUID
                ? 'Payment method mapping found: ' . $entry->getHandlerIdentifier()
                : 'No mapping found. Defaulting to generic payment method'
        );

        return $entry ? $entry->getId() : GenericPayment::UUID;
    }

    public function mapPaymentMethod(PaymentMethodEntity $paymentMethod): ShopgatePaymentMethod
    {
        $this->dispatcher->dispatch(new BeforePaymentMethodMapping($paymentMethod));
        $method = $this->classFactory->createPaymentMethod();
        $method->setId($this->getPaymentId($paymentMethod));
        $this->dispatcher->dispatch(new AfterPaymentMethodMapping($paymentMethod, $method));

        return $method;
    }

    private function getPaymentId(PaymentMethodEntity $paymentMethod): string
    {
        $id = '';
        if ($paymentMethod->getFormattedHandlerIdentifier() === 'handler_shopware_defaultpayment') {
            $id = "_{$paymentMethod->getId()}";
        }

        return $paymentMethod->getFormattedHandlerIdentifier() . $id;
    }
}
