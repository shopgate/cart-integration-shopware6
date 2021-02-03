<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Mapping\ConfigMapping;
use Shopgate\Shopware\System\Di\Facade;
use Shopgate\Shopware\Plugin;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateBuilder;
use ShopgateLibraryException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends StorefrontController
{
    /** @var ConfigBridge */
    private $systemConfigService;
    /** @var ContextManager */
    private $contextManager;
    /** @var SalesChannelContextFactory */
    private $channelContextFactory;

    /**
     * @param ConfigBridge $systemConfigService
     * @param ContainerInterface $container
     * @param ContextManager $context
     * @param SalesChannelContextFactory $channelContextFactory
     */
    public function __construct(
        ConfigBridge $systemConfigService,
        ContainerInterface $container,
        ContextManager $context,
        SalesChannelContextFactory $channelContextFactory
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->contextManager = $context;
        $this->channelContextFactory = $channelContextFactory;
        Facade::init($container); //todo: need to do this for non HTTP calls too
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/shopgate/plugin", name="shopgate_action", methods={"GET","POST"}, defaults={"csrf_protected": false})
     * @param Request $request
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        $salesChannelId = $this->systemConfigService->getSalesChannelId(
            $request->request->get('shop_number')
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
        $salesChannelContext = $this->channelContextFactory->create(Uuid::randomHex(), $salesChannelId);
        $this->contextManager->setSalesChannelContext($salesChannelContext);

        if ($salesChannelContext->getSalesChannel()->isMaintenance()) {
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
        $builder = new ShopgateBuilder($config);
        $plugin = new Plugin($builder);

        $plugin->handleRequest($request->request->all());

        exit;
    }
}
