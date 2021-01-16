<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Components;

use Shopgate\Shopware\Components\ConfigManager\ConfigReaderInterface;
use ShopgateConfig;

class Config extends ShopgateConfig
{
    public const SYSTEM_CONFIG_DOMAIN = 'ShopgateModule.config.';

    /** @var ConfigReaderInterface */
    protected $configReader;

    /**
     * @param ConfigReaderInterface $configReader
     */
    public function initShopwareConfig(ConfigReaderInterface $configReader): void
    {
        // TODO do we need to save the config to the class as a property?
        // TODO read all the data from config (work in progress)
        $this->configReader = $configReader;
        $this->setShopIsActive($this->configReader->get('isActive'));
        $this->setCustomerNumber($this->configReader->get('customerNumber'));
        $this->setShopNumber($this->configReader->get('shopNumber'));
        $this->setApikey($this->configReader->get('apiKey'));
        $this->loadArray(['enable_get_categories' => true]); // todo: can DI inject same as M2-Base
    }

    /**
     * @return bool
     */
    protected function startup(): bool
    {
        return false;
    }
}
