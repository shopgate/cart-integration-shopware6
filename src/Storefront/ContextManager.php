<?php

namespace Shopgate\Shopware\Storefront;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Holds our context for DI usage
 */
class ContextManager
{
    /** @var SalesChannelContextServiceInterface */
    private $contextService;
    /** @var SalesChannelContextRestorer */
    private $contextRestorer;
    /** @var SalesChannelContext|null */
    private $salesContext;
    /**
     * @var ContextSwitchRoute
     */
    private $contextSwitchRoute;

    /**
     * @param SalesChannelContextServiceInterface $contextService
     * @param SalesChannelContextRestorer $contextRestorer
     * @param ContextSwitchRoute $contextSwitchRoute
     */
    public function __construct(
        SalesChannelContextServiceInterface $contextService,
        SalesChannelContextRestorer $contextRestorer,
        ContextSwitchRoute $contextSwitchRoute
    ) {
        $this->contextService = $contextService;
        $this->contextRestorer = $contextRestorer;
        $this->contextSwitchRoute = $contextSwitchRoute;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return $this
     */
    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): ContextManager
    {
        $this->salesContext = $salesChannelContext;
        return $this;
    }

    /**
     * Will only throw if developer messes the context system up
     *
     * @return SalesChannelContext
     * @throws MissingContextException
     */
    public function getSalesContext(): SalesChannelContext
    {
        if (null === $this->salesContext) {
            throw new MissingContextException('Context not initialized');
        }
        return $this->salesContext;
    }

    /**
     * @param string $customerId
     * @return SalesChannelContext
     */
    public function loadByCustomerId(string $customerId): SalesChannelContext
    {
        $context = $this->contextRestorer->restore($customerId, $this->salesContext);

        return $this->salesContext = $context;
    }

    /**
     * @param RequestDataBag $dataBag
     * @return SalesChannelContext
     */
    public function switchContext(RequestDataBag $dataBag): SalesChannelContext
    {
        $token = $this->contextSwitchRoute->switchContext($dataBag, $this->salesContext)->getToken();
        $context = $this->loadByCustomerToken($token);

        return $this->salesContext = $context;
    }

    /**
     * @param string $token
     * @return SalesChannelContext
     */
    public function loadByCustomerToken(string $token): SalesChannelContext
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $context = $this->contextService->get(
            $this->salesContext->getSalesChannel()->getId(),
            $token,
            $this->salesContext->getSalesChannel()->getLanguageId(),
            $this->salesContext->getSalesChannel()->getCurrencyId()
        );

        return $this->salesContext = $context;
    }
}
