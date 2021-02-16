<?php

namespace Shopgate\Shopware\System\Db;

use Shopgate\Shopware\ShopgateModule;
use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopgate\Shopware\System\Db\PaymentMethod\PaymentMethodInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copied from PayOne
 */
class PaymentMethodInstaller
{
    public const PAYMENT_METHODS = [
        GenericPayment::class
    ];

    /** @var PluginIdProvider */
    private $pluginIdProvider;
    /** @var EntityRepositoryInterface */
    private $paymentMethodRepo;
    /** @var EntityRepositoryInterface */
    private $salesChannelRepo;
    /** @var EntityRepositoryInterface */
    private $paymentMethodSalesChannelRepo;

    /**
     * @param ContainerInterface $container
     * @noinspection MissingService
     */
    public function __construct(ContainerInterface $container)
    {
        $this->pluginIdProvider = $container->get(PluginIdProvider::class);
        $this->paymentMethodRepo = $container->get('payment_method.repository');
        $this->salesChannelRepo = $container->get('sales_channel.repository');
        $this->paymentMethodSalesChannelRepo = $container->get('sales_channel_payment_method.repository');
    }

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context): void
    {
        foreach ($this->getPaymentMethods() as $method) {
            $this->upsertPaymentMethod($method, $context->getContext());
            $this->enablePaymentMethodForAllChannels($method, $context->getContext());
        }
    }

    /**
     * @return array
     */
    private function getPaymentMethods(): array
    {
        return array_map(static function (string $method) {
            return new $method();
        }, self::PAYMENT_METHODS);
    }

    /**
     * @param PaymentMethodInterface $paymentMethod
     * @param Context $context
     */
    private function upsertPaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = [
            'id' => $paymentMethod->getId(),
            'handlerIdentifier' => $paymentMethod->getPaymentHandler(),
            'pluginId' => $this->pluginIdProvider->getPluginIdByBaseClass(ShopgateModule::class, $context),
            'afterOrderEnabled' => $paymentMethod->getAfterOrder()
        ];

        // Find existing payment method by ID for update / install decision
        $paymentMethodEntity = $this->findPaymentMethod($paymentMethod->getId(), $context);

        // Decide whether to update an existing or install a new payment method
        if ($paymentMethodEntity instanceof PaymentMethodEntity) {
            $this->updatePaymentMethod($data, $context);
        } else {
            $this->installPaymentMethod($data, $paymentMethod, $context);
        }
    }

    /**
     * @param string $id
     * @param Context $context
     * @return PaymentMethodEntity|null
     */
    private function findPaymentMethod(string $id, Context $context): ?PaymentMethodEntity
    {
        return $this->paymentMethodRepo->search(new Criteria([$id]), $context)->first();
    }

    /**
     * @param array $data
     * @param Context $context
     */
    private function updatePaymentMethod(array $data, Context $context): void
    {
        $this->paymentMethodRepo->update([$data], $context);
    }

    /**
     * @param array $info
     * @param PaymentMethodInterface $paymentMethod
     * @param Context $context
     */
    private function installPaymentMethod(array $info, PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $info = array_merge($info, [
            'name' => $paymentMethod->getName(),
            'description' => $paymentMethod->getDescription(),
            'position' => $paymentMethod->getPosition(),
        ]);

        $this->paymentMethodRepo->create([$info], $context);
    }

    /**
     * @param PaymentMethodInterface $method
     * @param Context $context
     */
    private function enablePaymentMethodForAllChannels(
        PaymentMethodInterface $method,
        Context $context
    ): void {
        $channels = $this->salesChannelRepo->searchIds(new Criteria(), $context);
        foreach ($channels->getIds() as $channel) {
            $data = [
                'salesChannelId' => $channel,
                'paymentMethodId' => $method->getId(),
            ];

            $this->paymentMethodSalesChannelRepo->upsert([$data], $context);
        }
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->setIsActive(true, $paymentMethod, $context->getContext());
        }
    }

    /**
     * @param bool $active
     * @param PaymentMethodInterface $paymentMethod
     * @param Context $context
     */
    private function setIsActive(bool $active, PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = ['id' => $paymentMethod->getId(), 'active' => $active,];

        if ($this->paymentMethodExists($data, $context) === false) {
            return;
        }

        $this->paymentMethodRepo->update([$data], $context);
    }

    /**
     * @param array $data
     * @param Context $context
     * @return bool
     */
    private function paymentMethodExists(array $data, Context $context): bool
    {
        if (empty($data['id'])) {
            return false;
        }

        $result = $this->paymentMethodRepo->search(new Criteria([$data['id']]), $context);

        return $result->getTotal() !== 0;
    }

    /**
     * Only deactivates payment methods, deleting can cause data issues.
     * e.g. orders should not be referencing a non-existing payment method.
     *
     * @param Context $context
     */
    public function deactivate(Context $context): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->setIsActive(false, $paymentMethod, $context);
        }
    }
}
