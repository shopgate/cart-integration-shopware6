<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront\Controller;

use Shopgate\Shopware\Catalog\Product\Sort\SortTree;
use Shopgate\Shopware\Shopgate\Catalog\CategoryProductIndexingMessage;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Log\FallbackLogger;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingRouteResponse;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class IndexController
{
    public function __construct(
        private readonly ContextManager $contextManager,
        private readonly EntityIndexerRegistry $registry,
        private readonly FallbackLogger $logger,
        private readonly SortTree $sortTree
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

    /**
     * @throws \JsonException
     */
    #[Route(path: '/api/shopgate/index/product-listing/{categoryId}', name: 'api.shopgate.index.test-productListing', defaults: [
        '_routeScope' => ['api'],
        '_contextTokenRequired' => true,
    ], methods: ['POST'])]
    public function forwardProductListRequestToIndex(string $categoryId, Request $request): ProductListingRouteResponse
    {
        $channelId = $request->get('salesChannelId');
        $languageId = $request->headers->get(PlatformRequest::HEADER_LANGUAGE_ID, Defaults::LANGUAGE_SYSTEM);
        $salesChannelContext = $this->contextManager->createNewContext(
            $channelId,
            [SalesChannelContextService::LANGUAGE_ID => $languageId]
        );
        $this->contextManager->overwriteSalesContext($salesChannelContext);

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 25);
        $sortId = $request->get('sortId');

        $category = new CategoryEntity();
        $category->setId($categoryId);
        $category->setName('API: shopgate test route');
        $sortId && $category->setSlotConfig([0 => ['defaultSorting' => ['value' => $sortId]]]);
        $result = $this->sortTree->getPaginatedCategoryProducts($category, $page, $limit);

        return new ProductListingRouteResponse($result);
    }
}
