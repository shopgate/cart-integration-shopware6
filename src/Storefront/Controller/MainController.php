<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Exception;
use ShopgateLibraryException;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class MainController extends StorefrontController
{
    public const IS_SHOPGATE = 'IS_SHOPGATE_CALL';

    public function __construct(private readonly MainApiController $apiController)
    {
    }

    #[Route(path: '/shopgate/plugin', name: 'frontend.shopgate.action', defaults: ['auth_required' => false], methods: [
        'GET',
        'POST'
    ])]
    /**
     * There is a preference on using the API endpoint instead
     *
     * @throws ShopgateLibraryException
     * @throws Exception
     */
    public function execute(Request $request): Response
    {
        return $this->apiController->execute($request);
    }
}
