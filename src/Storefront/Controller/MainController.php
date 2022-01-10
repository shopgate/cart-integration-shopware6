<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\Plugin;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends StorefrontController
{
    public const IS_SHOPGATE = 'IS_SHOPGATE_CALL';
    private ConfigBridge $systemConfigService;
    private ContextManager $contextManager;
    private Plugin $plugin;

    public function __construct(ConfigBridge $systemConfigService, ContextManager $contextManager, Plugin $plugin)
    {
        $this->systemConfigService = $systemConfigService;
        $this->contextManager = $contextManager;
        $this->plugin = $plugin;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/shopgate/plugin", name="shopgate_action", methods={"GET","POST"}, defaults={"csrf_protected": false})
     * @param Request $request
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        define(self::IS_SHOPGATE, true);
        if ($error = $this->systemConfigService->getError()) {
            return new JsonResponse($error);
        }
        $this->contextManager->createAndLoadByChannelId($this->systemConfigService->get('salesChannelId'));
        define('SHOPGATE_PLUGIN_VERSION', $this->systemConfigService->getShopgatePluginVersion());
        $this->plugin->handleRequest($request->request->all());

        exit;
    }
}
