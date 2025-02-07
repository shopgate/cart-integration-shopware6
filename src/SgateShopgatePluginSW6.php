<?php declare(strict_types=1);

namespace Shopgate\Shopware;

use Doctrine\DBAL\Connection;
use Shopgate\Shopware\Shopgate\ApiCredentials\ShopgateApiCredentialsDefinition;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductMappingDefinition;
use Shopgate\Shopware\Shopgate\Order\ShopgateOrderDefinition;
use Shopgate\Shopware\Shopgate\Salutations\ShopgateSalutationDefinition;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Db\Installers\DeliveryTimeInstaller;
use Shopgate\Shopware\System\Db\Installers\PaymentMethodInstaller;
use Shopgate\Shopware\System\Db\Installers\RuleConditionInstaller;
use Shopgate\Shopware\System\Db\Installers\RuleInstaller;
use Shopgate\Shopware\System\Db\Installers\ShippingMethodInstaller;
use Shopgate\Shopware\System\Db\Installers\ShippingMethodPriceInstaller;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

class SgateShopgatePluginSW6 extends Plugin
{

    public function install(InstallContext $installContext): void
    {
        /** @var SystemConfigService $configBridge */
        $configBridge = $this->container->get(SystemConfigService::class);
        $configBridge->set(
            ConfigBridge::SYSTEM_CONFIG_PROD_EXPORT,
            [ConfigBridge::PROD_EXPORT_TYPE_SIMPLE, ConfigBridge::PROD_EXPORT_TYPE_VARIANT]
        );
        (new RuleInstaller($this->container))->install($installContext);
        (new RuleConditionInstaller($this->container))->install();
        (new DeliveryTimeInstaller($this->container))->install($installContext);
        (new ShippingMethodInstaller($this->container))->install($installContext);
        (new ShippingMethodPriceInstaller($this->container))->install($installContext);
        (new PaymentMethodInstaller($this->container))->install($installContext);
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        (new ShippingMethodInstaller($this->container))->deactivate($uninstallContext->getContext());
        (new PaymentMethodInstaller($this->container))->deactivate($uninstallContext->getContext());
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        if ($connection = $this->container->get(Connection::class)) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
            $connection->executeStatement(sprintf('DROP TABLE IF EXISTS `%s`', ShopgateOrderDefinition::ENTITY_NAME));
            $connection->executeStatement(sprintf('DROP TABLE IF EXISTS `%s`',
                ShopgateSalutationDefinition::ENTITY_NAME));
            $connection->executeStatement(sprintf('DROP TABLE IF EXISTS `%s`',
                ShopgateApiCredentialsDefinition::ENTITY_NAME));
            $connection->executeStatement(sprintf('DROP TABLE IF EXISTS `%s`',
                CategoryProductMappingDefinition::ENTITY_NAME));
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    public function activate(Plugin\Context\ActivateContext $activateContext): void
    {
        (new ShippingMethodInstaller($this->container))->activate($activateContext);
        (new PaymentMethodInstaller($this->container))->activate($activateContext);
        parent::activate($activateContext);
    }

    public function deactivate(Plugin\Context\DeactivateContext $deactivateContext): void
    {
        (new ShippingMethodInstaller($this->container))->deactivate($deactivateContext->getContext());
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
