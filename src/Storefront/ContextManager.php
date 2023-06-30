<?php declare(strict_types=1);

namespace Shopgate\Shopware\Storefront;

use Shopgate\Shopware\Shopgate\ApiCredentials\ShopgateApiCredentialsEntity;
use Shopgate\Shopware\Storefront\Events\ContextChangedEvent;
use Shopware\Core\Framework\Routing\SalesChannelRequestContextResolver;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\CartRestorer;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Holds our context for DI usage
 */
class ContextManager
{
    private SalesChannelRequestContextResolver $contextResolver;
    private ?SalesChannelContext $salesContext = null;
    private AbstractSalesChannelContextFactory $channelContextFactory;
    private CartRestorer $cartRestorer;
    private AbstractContextSwitchRoute $contextSwitchRoute;
    private SalesChannelContextPersister $contextPersist;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        AbstractSalesChannelContextFactory $channelContextFactory,
        EventDispatcherInterface $eventDispatcher,
        SalesChannelRequestContextResolver $contextResolver,
        CartRestorer $cartRestorer,
        AbstractContextSwitchRoute $contextSwitchRoute,
        SalesChannelContextPersister $contextPersist
    ) {
        $this->contextResolver = $contextResolver;
        $this->cartRestorer = $cartRestorer;
        $this->contextSwitchRoute = $contextSwitchRoute;
        $this->channelContextFactory = $channelContextFactory;
        $this->contextPersist = $contextPersist;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Note 6.5+ the valid language is only in the SalesChannelContext->Context->getLanguageId()
     */
    public function createAndLoad(ShopgateApiCredentialsEntity $apiCredentialsEntity): ContextManager
    {
        $context = $this->createNewContext($apiCredentialsEntity->getSalesChannelId(),
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
        $this->overwriteSalesContext($context);

        return $this;
    }

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
        $request->headers->set(
            PlatformRequest::HEADER_LANGUAGE_ID,
            $baseContext->getCustomer()?->getLanguageId() ?? $baseContext->getContext()->getLanguageId()
        );
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID, $channel->getCurrencyId());
        $this->contextResolver->handleSalesChannelContext($request, $channel->getId(), $token);

        // resolver is intended to be used as an API, therefore it returns context in request
        $newContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
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
     * @return SalesChannelContext
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
