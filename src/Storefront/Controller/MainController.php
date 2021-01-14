<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\Plugin;
use ShopgateBuilder;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopgate\Shopware\Components\Config;
use Shopgate\Shopware\Components\ConfigReader\ConfigReaderInterface;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends StorefrontController
{
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
        // TODO authentication tested, remove if you are good
        //define('SHOPGATE_DEBUG', 1);

        $requestData = [];
        foreach ($this->getParameter('payload.key.whitelist') as $param) {
            if ($value = $request->get($param)) {
                $requestData[$param] = $value;
            }
        }

        // TODO use the specific sttorefront ID here. In theory we should be able to know which one it is from the path
        $this->systemConfigService->read();

        $config = new Config();
        $config->initShopwareConfig($this->systemConfigService);
        $builder = new ShopgateBuilder($config);
        $plugin = new Plugin($builder);

        $plugin->handleRequest($requestData);

        exit;
    }
}
