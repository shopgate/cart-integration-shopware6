<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Core;

use Exception;
use League\Flysystem\FilesystemInterface;
use Shopgate\Shopware\Plugin;
use Shopgate_Model_AbstractExport;
use Shopgate_Model_Catalog_Category;
use Shopgate_Model_Catalog_Product;
use Shopgate_Model_Catalog_Review;
use Shopgate_Model_XmlResultObject;
use ShopgateAuthenticationServiceOAuth;
use ShopgateAuthenticationServiceShopgate;
use ShopgateBuilder;
use ShopgateConfigInterface;
use ShopgateFileBufferCsv;
use ShopgateFileBufferJson;
use ShopgateMerchantApi;
use ShopgateObject;
use ShopgatePlugin;

class ExtendedBuilder extends ShopgateBuilder
{

    private FilesystemInterface $privateFileSystem;

    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(FilesystemInterface $privateFileSystem)
    {
        $this->privateFileSystem = $privateFileSystem;
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

        // instantiate API stuff
        // -> MerchantAPI auth service (needs to be initialized first, since the config still can change along with the authentication information
        switch ($this->config->getSmaAuthServiceClassName()) {
            case ShopgateConfigInterface::SHOPGATE_AUTH_SERVICE_CLASS_NAME_SHOPGATE:
                $smaAuthService = new ShopgateAuthenticationServiceShopgate(
                    $this->config->getCustomerNumber(),
                    $this->config->getApikey()
                );
                $smaAuthService->setup($this->config);
                $merchantApi = new ShopgateMerchantApi(
                    $smaAuthService, $this->config->getShopNumber(),
                    $this->config->getApiUrl()
                );
                break;
            case ShopgateConfigInterface::SHOPGATE_AUTH_SERVICE_CLASS_NAME_OAUTH:
                $smaAuthService = new ShopgateAuthenticationServiceOAuth($this->config->getOauthAccessToken());
                $smaAuthService->setup($this->config);
                $merchantApi = new ShopgateMerchantApi($smaAuthService, null, $this->config->getApiUrl());
                break;
            default:
                // undefined auth service
                trigger_error(
                    'Invalid SMA-Auth-Service defined - this should not happen with valid plugin code',
                    E_USER_ERROR
                );
        }
        // -> PluginAPI auth service (currently the plugin API supports only one auth service)
        $spaAuthService = new ShopgateAuthenticationServiceShopgate(
            $this->config->getCustomerNumber(),
            $this->config->getApikey()
        );
        $pluginApi = new ExtendedPluginApi(
            $this->config, $spaAuthService, $merchantApi, $plugin, null,
            $this->buildStackTraceGenerator(), $this->logging
        );

        if ($this->config->getExportConvertEncoding()) {
            array_splice(ShopgateObject::$sourceEncodings, 1, 0, $this->config->getEncoding());
            ShopgateObject::$sourceEncodings = array_unique(ShopgateObject::$sourceEncodings);
        }

        if ($this->config->getForceSourceEncoding()) {
            ShopgateObject::$sourceEncodings = array($this->config->getEncoding());
        }

        if (!empty($_REQUEST['action']) && in_array($_REQUEST['action'],
                ['get_items', 'get_categories', 'get_reviews'], true)
        ) {
            $xmlModelNames = [
                'get_items' => Shopgate_Model_Catalog_Product::class,
                'get_categories' => Shopgate_Model_Catalog_Category::class,
                'get_reviews' => Shopgate_Model_Catalog_Review::class,
            ];

            $format = $_REQUEST['response_type'] ?? '';
            switch ($format) {
                default:
                case 'xml':
                    /* @var $xmlModel Shopgate_Model_AbstractExport */
                    $xmlModel = new $xmlModelNames[$_REQUEST['action']]();
                    /** @noinspection PhpComposerExtensionStubsInspection */
                    $xmlNode = new Shopgate_Model_XmlResultObject($xmlModel->getItemNodeIdentifier());
                    $fileBuffer = new XmlFileBufferExtended(
                        $xmlModel,
                        $xmlNode,
                        $this->config->getExportBufferCapacity(),
                        $this->privateFileSystem,
                        $this->config->getExportConvertEncoding(),
                        ShopgateObject::$sourceEncodings
                    );
                    break;

                case 'json':
                    $fileBuffer = new ShopgateFileBufferJson(
                        $this->config->getExportBufferCapacity(),
                        $this->config->getExportConvertEncoding(),
                        ShopgateObject::$sourceEncodings
                    );
                    break;
            }
        } else {
            $fileBuffer = new ShopgateFileBufferCsv(
                $this->config->getExportBufferCapacity(),
                $this->config->getExportConvertEncoding(),
                ShopgateObject::$sourceEncodings
            );
        }
        // inject apis into plugin
        $pluginApi->setPrivateFileSystem($this->privateFileSystem);
        $plugin->setConfig($this->config);
        $plugin->setMerchantApi($merchantApi);
        $plugin->setPluginApi($pluginApi);
        $plugin->setBuffer($fileBuffer);
    }
}
