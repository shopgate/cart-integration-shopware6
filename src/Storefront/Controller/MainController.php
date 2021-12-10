<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\Plugin;
use Shopgate\Shopware\Shopgate\Extended\Core\ExtendedBuilder;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Di\Facade;
use Shopgate\Shopware\System\Mapping\ConfigMapping;
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
    private ExtendedBuilder $builder;

    public function __construct(
        ConfigBridge $systemConfigService,
        ContainerInterface $container,
        ContextManager $context,
        ExtendedBuilder $builder
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
        $salesChannelId = $this->systemConfigService->getSalesChannelId(
            (string) $request->request->get('shop_number')
        );
        if (null === $salesChannelId) {
            return new JsonResponse(
                [
                    'error' => ShopgateLibraryException::PLUGIN_API_UNKNOWN_SHOP_NUMBER,
                    'error_text' => 'No shop_number exists in the Shopgate configuration. Configure a specific channel.'
                ]
            );
        }
        $this->systemConfigService->load($salesChannelId);
        if ($this->systemConfigService->get('isActive') !== true) {
            return new JsonResponse([
                'error' => ShopgateLibraryException::CONFIG_PLUGIN_NOT_ACTIVE,
                'error_text' => 'Plugin is not active in Shopware config'
            ]);
        }
        $context = $this->contextManager->createNewContext(Random::getAlphanumericString(32), $salesChannelId);
        $this->contextManager->setSalesChannelContext($context);

        if ($context->getSalesChannel()->isMaintenance()) {
            return new JsonResponse([
                'error' => ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                'error_text' => 'site in maintenance mode'
            ]);
        }
        $actionWhitelist = array_map(static function ($item) {
            return (bool)$item;
        }, $this->getParameter('shopgate.action.whitelist'));
        $config = new ConfigMapping($actionWhitelist);
        $config->initShopwareConfig($this->systemConfigService);
        $this->builder->setConfig($config);
        $plugin = new Plugin($this->builder);

        $plugin->handleRequest($request->request->all());

        exit;
    }
}
