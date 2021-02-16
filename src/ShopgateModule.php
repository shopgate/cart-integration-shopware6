<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use Doctrine\DBAL\Connection;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Db\PaymentMethodInstaller;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ShopgateModule extends Plugin
{

    public function install(InstallContext $installContext): void
    {
        /** @var SystemConfigService $configBridge */
        $configBridge = $this->container->get(SystemConfigService::class);
        $configBridge->set(
            ConfigBridge::SYSTEM_CONFIG_PROD_EXPORT,
            [ConfigBridge::PROD_EXPORT_TYPE_SIMPLE, ConfigBridge::PROD_EXPORT_TYPE_VARIANT]
        );
        (new PaymentMethodInstaller($this->container))->install($installContext);
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        (new PaymentMethodInstaller($this->container))->deactivate($uninstallContext->getContext());
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        if ($connection = $this->container->get(Connection::class)) {
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0;');
            $connection->executeQuery('DROP TABLE IF EXISTS `shopgate_orders`');
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function activate(Plugin\Context\ActivateContext $activateContext): void
    {
        (new PaymentMethodInstaller($this->container))->activate($activateContext);
        parent::activate($activateContext);
    }

    public function deactivate(Plugin\Context\DeactivateContext $deactivateContext): void
    {
        (new PaymentMethodInstaller($this->container))->deactivate($deactivateContext->getContext());
        parent::deactivate($deactivateContext);
    }

    /**
     * Where you should look for Migration database scripts
     *
     * @return string
     */
    public function getMigrationNamespace(): string
    {
        return 'Shopgate\Shopware\System\Db\Migration';
    }
}
