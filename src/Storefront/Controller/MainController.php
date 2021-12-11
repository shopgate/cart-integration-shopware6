<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\Plugin;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Di\Facade;
use ShopgateBuilder;
use ShopgateLibraryException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Util\Random;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends StorefrontController
{
    public const IS_SHOPGATE = 'IS_SHOPGATE_CALL';
    private ConfigBridge $systemConfigService;
    private ContextManager $contextManager;
    private ShopgateBuilder $builder;

    public function __construct(
        ConfigBridge $systemConfigService,
        ContainerInterface $container,
        ContextManager $context,
        ShopgateBuilder $builder
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->contextManager = $context;
        $this->builder = $builder;
        Facade::init($container);
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
        $salesChannelId = $this->systemConfigService->getSalesChannelId(
            (string)$request->request->get('shop_number')
        );
        $context = $this->contextManager->createNewContext(Random::getAlphanumericString(32), $salesChannelId);
        $this->contextManager->setSalesChannelContext($context);

        if ($context->getSalesChannel()->isMaintenance()) {
            return new JsonResponse([
                'error' => ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                'error_text' => 'site in maintenance mode'
            ]);
        }
        $plugin = new Plugin($this->builder);
        $plugin->handleRequest($request->request->all());

        exit;
    }
}
