<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Mapping;

use Shopgate\Shopware\System\Configuration\ConfigBridge;
use ShopgateConfig;
use Symfony\Component\Filesystem\Filesystem;

class ConfigMapping extends ShopgateConfig
{
    protected ConfigBridge $configReader;

    public function setConfigBridge(ConfigBridge $configReader): ConfigMapping
    {
        $this->configReader = $configReader;

        return $this;
    }

    public function initShopwareConfig(array $data = []): void
    {
        $this->loadArray($data);
        $this->setLogFolderPath(implode('/', [$this->getLogFolderPath(), $this->getShopNumber()]));
        $this->setCacheFolderPath(implode('/', [$this->getCacheFolderPath(), $this->getShopNumber()]));
        $this->setExportFolderPath(implode('/', [$this->getExportFolderPath(), $this->getShopNumber()]));
    }

    public function initFolderStructure(Filesystem $filesystem): void
    {
        array_map(static function (string $path) use ($filesystem) {
            if (!$filesystem->exists($path)) {
                $filesystem->mkdir($path);
            }
        }, [$this->getLogFolderPath(), $this->getCacheFolderPath(), $this->getExportFolderPath()]);
    }
}
