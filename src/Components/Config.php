<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Components;

use Shopgate\Shopware\Components\ConfigReader\ConfigReader;
use Shopgate\Shopware\Components\ConfigReader\ConfigReaderInterface;
use ShopgateConfig;

class Config extends ShopgateConfig
{
    public const SYSTEM_CONFIG_DOMAIN = 'ShopgateModule.config.';

    /** @var ConfigReaderInterface */
    protected $configReader;

    /**
     * @return bool
     */
    protected function startup()
    {
        return true;
    }

    /**
     * @param ConfigReader $configReader
     * @return bool
     */
    public function initShopwareConfig( ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
        $this->setShopIsActive($this->configReader->get('isActive'));
        $this->setCustomerNumber($this->configReader->get('customerNumber'));
        $this->setShopNumber($this->configReader->get('shopNumber'));
        $this->setApikey($this->configReader->get('apiKey'));
    }
}
