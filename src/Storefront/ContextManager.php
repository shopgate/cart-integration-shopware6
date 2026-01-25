<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront;

use JsonException;
use Shopgate\Shopware\Shopgate\ApiCredentials\ShopgateApiCredentialsEntity;
use Shopgate\Shopware\Storefront\Events\ContextChangedEvent;
use Shopware\Administration\Framework\Routing\AdministrationRouteScope;
use Shopware\Core\Framework\Routing\ApiRouteScope;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\Framework\Routing\SalesChannelRequestContextResolver;
use Shopware\Core\Framework\Routing\StoreApiRouteScope;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\CartRestorer;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Holds our context for DI usage
 */
class ContextManager
{
    private ?SalesChannelContext $salesContext = null;

    public function __construct(
        private readonly AbstractSalesChannelContextFactory $channelContextFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SalesChannelRequestContextResolver $contextResolver,
        private readonly SalesChannelContextService $contextService,
        private readonly CartRestorer $cartRestorer,
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly SalesChannelContextPersister $contextPersist
    ) {
    }

    /**
     * Note 6.5+ the valid language is only in the SalesChannelContext->Context->getLanguageId()
     * @throws JsonException
     */
    public function createAndLoad(ShopgateApiCredentialsEntity $apiCredentialsEntity): ContextManager
    {
        $context = $this->createNewContext(
            $apiCredentialsEntity->getSalesChannelId(),
            [SalesChannelContextService::LANGUAGE_ID => $apiCredentialsEntity->getLanguageId()]
        );
        $this->overwriteSalesContext($context);

        return $this;
    }

    public function getSalesContext(): SalesChannelContext
    {
        return $this->salesContext;
    }

    public function overwriteSalesContext(SalesChannelContext $context): ContextManager
    {
        $this->salesContext = $context;
        $this->eventDispatcher->dispatch(new ContextChangedEvent($context));

        return $this;
    }

    public function loadByCustomerId(string $customerId): ContextManager
    {
        $context = $this->cartRestorer->restore($customerId, $this->salesContext);
        // loads customer object until 6.7.5.1
        if (!$context->getCustomer()) {
            $contextServiceParameters = new SalesChannelContextServiceParameters(
                $this->salesContext->getSalesChannelId(),
                $context->getToken(),
                $context->getLanguageId(),
                $context->getCurrencyId(),
                $context->getDomainId(),
                $context->getContext(),
                $customerId,
                null,
                null
            );
            $context = $this->contextService->get($contextServiceParameters);
        }
        $this->overwriteSalesContext($context);

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function switchContext(RequestDataBag $dataBag, ?SalesChannelContext $context = null): ContextManager
    {
        $currentContext = $context ?: $this->getSalesContext();
        $customerId = $currentContext->getCustomer()?->getId();
        $this->contextPersist->save(
            $currentContext->getToken(),
            $dataBag->all(),
            $currentContext->getSalesChannelId(),
            $dataBag->get(SalesChannelContextService::CUSTOMER_ID) ?: $customerId
        );
        $token = $this->contextSwitchRoute->switchContext($dataBag, $currentContext)->getToken();
        $this->loadByCustomerToken($token, $currentContext);

        return $this;
    }

    public function loadByCustomerToken(string $token, ?SalesChannelContext $context = null): ContextManager
    {
        $baseContext = $context ?? $this->getSalesContext();
        $channel = $baseContext->getSalesChannel();
        $request = new Request();
        $langId = $baseContext->getCustomer()?->getLanguageId() ?? $baseContext->getContext()->getLanguageId();
        $request->headers->set(PlatformRequest::HEADER_LANGUAGE_ID, $langId);
        $request->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $token);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, $baseContext->getSalesChannelId());
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID, $channel->getCurrencyId());
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $baseContext->getContext());
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [StoreApiRouteScope::ID]);
        $this->contextResolver->resolve($request);

        // not null until 6.7.5.1
        $newContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        if (!$newContext) {
            $contextServiceParameters = new SalesChannelContextServiceParameters(
                $baseContext->getSalesChannelId(),
                $token,
                $langId,
                $channel->getCurrencyId(),
                $baseContext->getDomainId(),
                $baseContext->getContext(),
                null,
                null,
                null
            );
            $newContext = $this->contextService->get($contextServiceParameters);
        }
        $this->overwriteSalesContext($newContext);

        return $this;
    }

    /**
     * Creates a duplicate of current context with a new token.
     * We can then manipulate the context & cart without fear
     * of messing with the desktop context & cart.
     *
     * @param SalesChannelContext $context
     * @param string|null $customerId
     *
     * @return SalesChannelContext
     * @throws JsonException
     */
    public function duplicateContextWithNewToken(SalesChannelContext $context, ?string $customerId): SalesChannelContext
    {
        $options = [
            SalesChannelContextService::LANGUAGE_ID => $context->getContext()->getLanguageId(),
            SalesChannelContextService::CURRENCY_ID => $context->getSalesChannel()->getCurrencyId(),
            SalesChannelContextService::PERMISSIONS => $context->getPermissions(),
            SalesChannelContextService::CUSTOMER_ID => !empty($customerId) ? $customerId : null,
        ];

        return $this->createNewContext($context->getSalesChannelId(), $options);
    }

    /**
     * @throws JsonException
     */
    public function createNewContext(
        string $salesChannelId,
        array $options = [],
        string $token = null
    ): SalesChannelContext {
        if (null === $token) {
            $token = Random::getAlphanumericString(32);
        }
        $this->contextPersist->save(
            $token,
            $options,
            $salesChannelId,
            $options[SalesChannelContextService::CUSTOMER_ID] ?? null
        );

        return $this->channelContextFactory->create($token, $salesChannelId, $options);
    }
}
