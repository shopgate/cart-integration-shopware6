<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\Plugin;
use ShopgateBuilder;
use ShopgateConfig;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopgate\Shopware\Components\Config;
use Shopgate\Shopware\Components\ConfigReader\ConfigReaderInterface;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends StorefrontController
{
    // TODO this needs to contain all possible params for all supported methods
    // TODO should this be a const?
    /** @var array */
    protected $params = ['action', 'apikey', 'customer_number', 'shop_number', 'cart', 'user', 'pass'];

    /** @var ConfigReaderInterface */
    private $systemConfigService;

    public function __construct(ConfigReaderInterface $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }
    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/shopgate/plugin", name="shopgate_action", methods={"GET","POST"}, defaults={"csrf_protected": false})
     * @param Request $request
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        // TODO remove this to enable authentication
        define('SHOPGATE_DEBUG', 1);

        $requestData = [];
        foreach ($this->params as $param) {
            if ($value = $request->get($param)) {
                $requestData[$param] = $value;
            }
        }

        $this->systemConfigService->read();

        // TODO read plugin config in a shopware specific config class, instead of using the default class
        $config = new Config();
        $config->initShopwareConfig($this->systemConfigService);
        $builder = new ShopgateBuilder($config);
        $plugin = new Plugin($builder);

        $plugin->handleRequest($requestData);

        exit;
    }
}
