<?php

declare(strict_types=1);

namespace Apite\Shopware;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class ShopgateModule extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
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