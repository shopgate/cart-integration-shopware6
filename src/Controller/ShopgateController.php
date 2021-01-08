<?php

declare(strict_types=1);

namespace Apite\Shopware\Controller;

use ShopgateConfig;
use ShopgateBuilder;
use Apite\Shopware\Plugin;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ShopgateController
{

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/shopgate/plugin", name="shopgate_action", methods={"GET","POST"}, defaults={"csrf_protected": false})
     */
    public function execute(Request $request): JsonResponse
    {
        $requestData = [];

        // todo get all necessary params
        if ($action = $request->get('action')) {
            $requestData['action'] = $action;
        }

        $config  = new ShopgateConfig();
        $builder = new ShopgateBuilder($config);
        $plugin  = new Plugin($builder);

        $plugin->handleRequest($requestData);

        exit;
    }
}