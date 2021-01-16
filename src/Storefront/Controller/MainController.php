<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\ConnectSdk\Exception\Exception;
use Shopgate\Shopware\Components\Di\Facade;
use Shopgate\Shopware\Plugin;
use ShopgateBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopgate\Shopware\Components\Config;
use Shopgate\Shopware\Components\ConfigManager\ConfigReaderInterface;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;

class MainController extends StorefrontController
{
    /** @var ConfigReaderInterface */
    private $systemConfigService;
    /** @var Facade */
    private $facade;

    /**
     * @param ConfigReaderInterface $systemConfigService
     * @param ContainerInterface $container
     */
    public function __construct(ConfigReaderInterface $systemConfigService, ContainerInterface $container)
    {
        $this->systemConfigService = $systemConfigService;
        Facade::init($container); //todo: need to do this for non HTTP calls too
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/shopgate/plugin", name="shopgate_action", methods={"GET","POST"}, defaults={"csrf_protected": false})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function execute(Request $request, Context $context): JsonResponse
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

        try {
            $pluginRepository = Facade::create('plugin.repository');
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', 'ShopgateModule'));
            $result = $pluginRepository->search($criteria, $context)->first();
            $version = $result->getVersion();
        } catch (Exception $e) {
            $version = 'not installed';
        }
        define("SHOPGATE_PLUGIN_VERSION", $version);

//        $request->attributes->get('sw-domain-id');
//        $request->attributes->get('sw-currency-id');
        $this->systemConfigService->read($request->attributes->get('sw-sales-channel-id'));

        $config = new Config(['facade' => $this->facade]);
        $config->initShopwareConfig($this->systemConfigService);
        $builder = new ShopgateBuilder($config);
        $plugin = new Plugin($builder);

        $plugin->setContext($context);
        $plugin->handleRequest($requestData);

        exit;
    }
}
