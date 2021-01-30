<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Mapping;

use Shopgate\Shopware\System\Configuration\ConfigBridge;
use ShopgateConfig;

class ConfigMapping extends ShopgateConfig
{
    /** @var ConfigBridge */
    protected $configReader;

    /**
     * @param ConfigBridge $configReader
     */
    public function initShopwareConfig(ConfigBridge $configReader): void
    {
        // TODO do we need to save the config to the class as a property?
        // TODO read all the data from config (work in progress)
        $this->configReader = $configReader;
        $this->setShopIsActive($this->configReader->get('isActive'));
        $this->setCustomerNumber($this->configReader->get('customerNumber'));
        $this->setShopNumber($this->configReader->get('shopNumber'));
        $this->setApikey($this->configReader->get('apiKey'));
    }

    /**
     * @return bool
     */
    protected function startup(): bool
    {
        $this->setPluginName('Shopgate Go Plugin for Shopware 6');
        return parent::startup();
    }
}
