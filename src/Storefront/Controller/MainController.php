<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Exception;
use Shopgate\Shopware\Plugin;
use Shopgate\Shopware\Shopgate\Extended\Flysystem\ExtendedApiResponseXmlExport;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use ShopgateLibraryException;
use ShopgatePluginApiResponseAppJson;
use ShopgatePluginApiResponseAppXmlExport;
use ShopgatePluginApiResponseTextPlain;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class MainController extends StorefrontController
{
    public const IS_SHOPGATE = 'IS_SHOPGATE_CALL';

    public function __construct(private readonly ConfigBridge $systemConfigService, private readonly Plugin $plugin)
    {
    }

    #[Route(path: '/shopgate/plugin', name: 'shopgate_action', defaults: ['csrf_protected' => false], methods: [
        'GET',
        'POST'
    ])]
    /**
     * @throws ShopgateLibraryException
     * @throws Exception
     */
    public function execute(Request $request): Response
    {
        define(self::IS_SHOPGATE, true);
        if ($error = $this->systemConfigService->getError()) {
            return new JsonResponse($error);
        }
        define('SHOPGATE_PLUGIN_VERSION', $this->systemConfigService->getShopgatePluginVersion());
        $result = $this->plugin->handleRequest($request->request->all());

        if ($result instanceof ShopgatePluginApiResponseTextPlain) {
            return new Response($result->getBody(), Response::HTTP_OK);
        } elseif ($result instanceof ShopgatePluginApiResponseAppJson) {
            return new JsonResponse(json_decode($result->getBody(), true), Response::HTTP_OK);
        } elseif ($result instanceof ShopgatePluginApiResponseAppXmlExport || $result instanceof ExtendedApiResponseXmlExport) {
            return new StreamedResponse(fn() => $result->send());
        }
        throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, 'No mapped response assigned');
    }
}
