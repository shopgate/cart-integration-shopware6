<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\SgateShopgatePluginSW6;
use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate\Shopware\System\Db\PaymentMethod\GenericPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodInstaller extends EntityChannelInstaller
{
    use EntityActivateTrait;

    protected array $entityInstallList = [
        GenericPayment::class
    ];
    protected string $entityName = 'payment_method';
    /** @var PluginIdProvider */
    private $pluginIdProvider;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->pluginIdProvider = $container->get(PluginIdProvider::class);
    }

    /**
     * Rewritten because of pluginId field
     *
     * @param ClassCastInterface $entity
     * @param Context $context
     */
    protected function upsertEntity(ClassCastInterface $entity, Context $context): void
    {
        $data = array_merge($entity->toArray(), [
            'pluginId' => $this->pluginIdProvider->getPluginIdByBaseClass(SgateShopgatePluginSW6::class, $context),
        ]);

        // Find existing payment method by ID for update / install decision
        $paymentMethodEntity = $this->findEntity($entity->getId(), $context);

        // Decide whether to update an existing or install a new payment method
        if ($paymentMethodEntity instanceof PaymentMethodEntity) {
            $this->updateEntity($data, $context);
        } else {
            $this->installEntity($data, $context);
        }
    }
}
