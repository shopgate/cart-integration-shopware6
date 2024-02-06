<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Flysystem;

use Exception;
use Shopgate\Shopware\Plugin;
use Shopgate_Model_AbstractExport;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Catalog_Product;
use Shopgate_Model_Catalog_Review;
use Shopgate_Model_XmlResultObject;
use ShopgateAuthenticationServiceInterface;
use ShopgateBuilder;
use ShopgateConfigInterface;
use ShopgateFileBufferJson;
use ShopgateMerchantApiInterface;
use ShopgateObject;
use ShopgatePlugin;
use Shopware\Core\Framework\Adapter\Filesystem\PrefixFilesystem;

class ExtendedBuilder extends ShopgateBuilder
{

    /**
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        private readonly PrefixFilesystem $privateFileSystem,
        private readonly ShopgateMerchantApiInterface $merchantApi,
        private readonly ShopgateAuthenticationServiceInterface $authService
    ) {
    }

    /**
     * @required
     */
    public function initConstruct(ShopgateConfigInterface $config): ExtendedBuilder
    {
        $config->setExportFolderPath('export');
        parent::__construct($config);

        return $this;
    }

    /**
     * @param Plugin $plugin
     * @throws Exception
     */
    public function buildLibraryFor(ShopgatePlugin $plugin): void
    {
        // set error handler if configured
        if ($this->config->getUseCustomErrorHandler()) {
            set_error_handler('ShopgateErrorHandler');
        }

        $pluginApi = new ExtendedPluginApi(
            $this->config, $this->authService, $this->merchantApi, $plugin,
            $this->buildStackTraceGenerator(), $this->logging
        );

        if ($this->config->getExportConvertEncoding()) {
            array_splice(ShopgateObject::$sourceEncodings, 1, 0, $this->config->getEncoding());
            ShopgateObject::$sourceEncodings = array_unique(ShopgateObject::$sourceEncodings);
        }

        if ($this->config->getForceSourceEncoding()) {
            ShopgateObject::$sourceEncodings = array($this->config->getEncoding());
        }

        $xmlModelNames = [
            'get_items' => Shopgate_Model_Catalog_Product::class,
            'get_categories' => Shopgate_Model_Catalog_Category::class,
            'get_reviews' => Shopgate_Model_Catalog_Review::class,
        ];
        if (isset($xmlModelNames[$_REQUEST['action']])) {
            /* @var $xmlModel Shopgate_Model_AbstractExport */
            $xmlModel = new $xmlModelNames[$_REQUEST['action']]();
            $xmlNode = new Shopgate_Model_XmlResultObject($xmlModel->getItemNodeIdentifier());
            $fileBuffer = new XmlFileBufferExtended(
                $xmlModel,
                $xmlNode,
                $this->config->getExportBufferCapacity(),
                $this->privateFileSystem,
                $this->config->getExportConvertEncoding(),
                ShopgateObject::$sourceEncodings
            );
        } else {
            $fileBuffer = new ShopgateFileBufferJson(
                $this->config->getExportBufferCapacity(),
                $this->config->getExportConvertEncoding(),
                ShopgateObject::$sourceEncodings
            );
        }
        // inject apis into plugin
        $pluginApi->setPrivateFileSystem($this->privateFileSystem);
        $pluginApi->setBuffer($fileBuffer);
        $plugin->setConfig($this->config);
        $plugin->setMerchantApi($this->merchantApi);
        $plugin->setPluginApi($pluginApi);
        $plugin->setBuffer($fileBuffer);
    }
}
