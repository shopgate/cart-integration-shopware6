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
        if ($request->attributes->getBoolean('sw-maintenance', true)) {
            return new JsonResponse('Site in maintenance', 503); // todo-prod
        }
        if (!$request->attributes->has('sw-sales-channel-id')) {
            return new JsonResponse('SalesChannel does not exist', 404); // todo-prod
        }

        $requestData = [];
        foreach ($this->getParameter('payload.key.whitelist') as $param) {
            if ($value = $request->get($param)) {
                $requestData[$param] = $value;
            }
        }

//        $request->attributes->get('sw-domain-id');
//        $request->attributes->get('sw-currency-id');
        $this->systemConfigService->read($request->attributes->get('sw-sales-channel-id'));

        $config = new Config();
        $config->initShopwareConfig($this->systemConfigService);
        $builder = new ShopgateBuilder($config);
        $plugin = new Plugin($builder);

        $plugin->handleRequest($requestData);

        exit;
    }
}
