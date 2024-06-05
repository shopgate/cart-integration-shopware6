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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class MainApiController extends AbstractController
{
    public function __construct(private readonly ConfigBridge $systemConfigService, private readonly Plugin $plugin)
    {
    }

    #[Route(path: '/api/shopgate/plugin', name: 'api.shopgate.action', defaults: ['auth_required' => false], methods: [
        'GET',
        'POST'
    ])]
    /**
     * @throws ShopgateLibraryException
     * @throws Exception
     */
    public function execute(Request $request): Response
    {
        define(MainController::IS_SHOPGATE, true);
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
