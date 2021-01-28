<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\Components\Config;
use Shopgate\Shopware\Components\ConfigManager\ConfigReaderInterface;
use Shopgate\Shopware\Components\Di\Facade;
use Shopgate\Shopware\Plugin;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateBuilder;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends StorefrontController
{
    /** @var ConfigReaderInterface */
    private $systemConfigService;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param ConfigReaderInterface $systemConfigService
     * @param ContainerInterface $container
     * @param ContextManager $context
     */
    public function __construct(
        ConfigReaderInterface $systemConfigService,
        ContainerInterface $container,
        ContextManager $context
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->contextManager = $context;
        Facade::init($container); //todo: need to do this for non HTTP calls too
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/shopgate/plugin", name="shopgate_action", methods={"GET","POST"}, defaults={"csrf_protected": false})
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return JsonResponse
     */
    public function execute(Request $request, SalesChannelContext $salesChannelContext): JsonResponse
    {
        if ($request->attributes->getBoolean('sw-maintenance', true)) {
            return new JsonResponse('Site in maintenance', 503); // todo-prod
        }
        if (!$request->attributes->has('sw-sales-channel-id')) {
            return new JsonResponse('SalesChannel does not exist', 404); // todo-prod
        }

        $this->systemConfigService->read($request->attributes->get('sw-sales-channel-id'));
        if ($this->systemConfigService->get('isActive') !== true) {
            return new JsonResponse('Plugin is not active in Shopware config', 503);
        }
        $this->contextManager->setSalesChannelContext($salesChannelContext);

        $actionWhitelist = array_map(static function ($item) {
            return (bool)$item;
        }, $this->getParameter('shopgate.action.whitelist'));
        $config = new Config($actionWhitelist);
        $config->initShopwareConfig($this->systemConfigService);
        $builder = new ShopgateBuilder($config);
        $plugin = new Plugin($builder);

        $plugin->handleRequest($request->request->all());

        exit;
    }
}
