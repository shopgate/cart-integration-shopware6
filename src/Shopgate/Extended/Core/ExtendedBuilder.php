<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Core;

use Exception;
use Shopgate_Model_AbstractExport;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Catalog_Product;
use Shopgate_Model_Catalog_Review;
use Shopgate_Model_XmlResultObject;
use ShopgateAuthenticationServiceInterface;
use ShopgateBuilder;
use ShopgateConfigInterface;
use ShopgateFileBufferJson;
use ShopgateFileBufferXml;
use ShopgateMerchantApiInterface;
use ShopgateObject;
use ShopgatePlugin;
use ShopgatePluginApi;

class ExtendedBuilder extends ShopgateBuilder
{
    private ShopgateAuthenticationServiceInterface $authService;
    private ShopgateMerchantApiInterface $merchantApi;

    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        ShopgateMerchantApiInterface $merchantApi,
        ShopgateAuthenticationServiceInterface $authService
    ) {
        $this->merchantApi = $merchantApi;
        $this->authService = $authService;
    }

    /**
     * Primary way of initializing the original config
     */
    public function initConstruct(ShopgateConfigInterface $config): ExtendedBuilder
    {
        parent::__construct($config);

        return $this;
    }

    /**
     * Rewriting the original to initialize some classes with
     * own extended versions
     * @throws Exception
     */
    public function buildLibraryFor(ShopgatePlugin $plugin)
    {
        // set error handler if configured
        if ($this->config->getUseCustomErrorHandler()) {
            set_error_handler('ShopgateErrorHandler');
        }

        $pluginApi = new ShopgatePluginApi(
            $this->config, $this->authService, $this->merchantApi, $plugin, null,
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
            /** @noinspection PhpComposerExtensionStubsInspection */
            $xmlNode = new Shopgate_Model_XmlResultObject($xmlModel->getItemNodeIdentifier());
            $fileBuffer = new ShopgateFileBufferXml(
                $xmlModel,
                $xmlNode,
                $this->config->getExportBufferCapacity(),
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
        $plugin->setConfig($this->config);
        $plugin->setMerchantApi($this->merchantApi);
        $plugin->setPluginApi($pluginApi);
        $plugin->setBuffer($fileBuffer);
    }
}
