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
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
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
    private SalesChannelContextRestorer $contextRestorer;
    private AbstractContextSwitchRoute $contextSwitchRoute;
    private SalesChannelContextPersister $contextPersist;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        AbstractSalesChannelContextFactory $channelContextFactory,
        EventDispatcherInterface $eventDispatcher,
        SalesChannelRequestContextResolver $contextResolver,
        SalesChannelContextRestorer $contextRestorer,
        AbstractContextSwitchRoute $contextSwitchRoute,
        SalesChannelContextPersister $contextPersist
    ) {
        $this->contextResolver = $contextResolver;
        $this->contextRestorer = $contextRestorer;
        $this->contextSwitchRoute = $contextSwitchRoute;
        $this->channelContextFactory = $channelContextFactory;
        $this->contextPersist = $contextPersist;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * This function will not assign language starting SW 6.5.
     * To rework, just search for `getLanguageId`
     */
    public function createAndLoad(ShopgateApiCredentialsEntity $apiCredentialsEntity): ContextManager
    {
        $salesChannelContext = $this->createNewContext($apiCredentialsEntity->getSalesChannelId(),
            [SalesChannelContextService::LANGUAGE_ID => $apiCredentialsEntity->getLanguageId()]
        );
        $this->overwriteSalesContext($salesChannelContext);

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

    /**
     * @todo 3.x will return $this
     */
    public function loadByCustomerId(string $customerId): SalesChannelContext
    {
        $context = $this->contextRestorer->restore($customerId, $this->salesContext);
        $this->overwriteSalesContext($context);

        return $context;
    }

    /**
     * Resetting is necessary as our transactions use hidden methods.
     * Without resetting the new objects created will use the last
     * context as base.
     * @deprecated 3.x will be removed
     */
    public function resetContext(?SalesChannelContext $context = null): void
    {
        $payment = $this->salesContext->getCustomer() && $this->salesContext->getCustomer()->getDefaultPaymentMethod()
            ? $this->salesContext->getCustomer()->getDefaultPaymentMethod()->getId()
            : $this->salesContext->getSalesChannel()->getPaymentMethodId();
        $shipping = $this->salesContext->getSalesChannel()->getShippingMethodId();
        $this->switchContext(
            new RequestDataBag([
                SalesChannelContextService::PAYMENT_METHOD_ID => $payment,
                SalesChannelContextService::SHIPPING_METHOD_ID => $shipping
            ]), $context);
    }

    /**
     * @todo 3.x will return $this
     */
    public function switchContext(RequestDataBag $dataBag, ?SalesChannelContext $context = null): SalesChannelContext
    {
        $currentContext = $context ?: $this->getSalesContext();
        $customerId = $currentContext->getCustomer() ? $currentContext->getCustomer()->getId() : null;
        $this->contextPersist->save(
            $currentContext->getToken(),
            $dataBag->all(),
            $currentContext->getSalesChannelId(),
            $dataBag->get(SalesChannelContextService::CUSTOMER_ID) ?: $customerId
        );
        $token = $this->contextSwitchRoute->switchContext($dataBag, $currentContext)->getToken();

        return $this->loadByCustomerToken($token, $currentContext);
    }

    /**
     * @todo 3.x will return $this
     */
    public function loadByCustomerToken(string $token, ?SalesChannelContext $context = null): SalesChannelContext
    {
        $channel = $context ? $context->getSalesChannel() : $this->getSalesContext()->getSalesChannel();
        $request = new Request();
        $request->headers->set(PlatformRequest::HEADER_LANGUAGE_ID, $channel->getLanguageId());
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID, $channel->getCurrencyId());
        $this->contextResolver->handleSalesChannelContext($request, $channel->getId(), $token);

        // resolver is intended to be used as an API, therefore it returns context in request
        $newContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $this->overwriteSalesContext($newContext);

        return $newContext;
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
            SalesChannelContextService::LANGUAGE_ID => $context->getSalesChannel()->getLanguageId(),
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
