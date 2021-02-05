<?php

declare(strict_types=1);

namespace Shopgate\Shopware;

use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ShopgateModule extends Plugin
{

    /**
     * @param InstallContext $installContext
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        /** @var SystemConfigService $configBridge */
        $configBridge = $this->container->get(SystemConfigService::class);
        $configBridge->set(
            ConfigBridge::SYSTEM_CONFIG_PROD_EXPORT,
            [ConfigBridge::PROD_EXPORT_TYPE_SIMPLE, ConfigBridge::PROD_EXPORT_TYPE_VARIANT]
        );
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

//        $connection = $this->container->get(Connection::class);
//        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0;');
//        $connection->executeQuery('DROP TABLE IF EXISTS `shopgate_orders`');
//        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);

//        (new Update())->update($this->container, $updateContext);
//
//        if (version_compare($updateContext->getCurrentPluginVersion(), '1.0.1', '<')) {
//            // stuff
//        }
    }
}
