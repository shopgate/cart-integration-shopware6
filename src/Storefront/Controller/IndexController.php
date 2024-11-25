<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\Shopgate\Catalog\CategoryProductIndexingMessage;
use Shopgate\Shopware\System\Log\FallbackLogger;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class IndexController
{
    public function __construct(
        private readonly EntityIndexerRegistry $registry,
        private readonly FallbackLogger $logger
    ) {
    }

    #[Route(path: '/api/shopgate/index/categories', name: 'api.shopgate.index.category', defaults: [
        '_routeScope' => ['api'],
        '_contextTokenRequired' => true,
    ], methods: [
        'POST'
    ])]
    public function categories(Request $request): JsonResponse
    {
        if (!$request->request->has('ids')) {
            throw new BadRequestHttpException('Parameter `ids` missing');
        }

        $ids = $request->request->all('ids');

        if (empty($ids)) {
            throw new BadRequestHttpException('Parameter `ids` is no array or empty');
        }

        $skips = array_filter(explode(',', (string) $request->headers->get(PlatformRequest::HEADER_INDEXING_SKIP, '')));
        $message = new CategoryProductIndexingMessage($ids, null, forceQueue: true);
        $message->addSkip(...$skips);

        // running product index before category indexing
        $this->registry->index(false, only: ['product.indexer']);
        $this->logger->logBasics('Running partial index via API', ['categories' => array_values($ids)]);

        $indexer = $this->registry->getIndexer('shopgate.go.category.product.mapping.indexer');
        $indexer?->handle($message);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
