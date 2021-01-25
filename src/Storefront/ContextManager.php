<?php

namespace Shopgate\Shopware\Storefront;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Holds our context for DI usage
 */
class ContextManager
{
    /** @var SalesChannelContextService */
    private $contextService;
    /** @var SalesChannelContext|null */
    private $salesContext;

    /**
     * ContextManager constructor.
     * @param SalesChannelContextService $contextService
     */
    public function __construct(
        SalesChannelContextService $contextService
    ) {
        $this->contextService = $contextService;
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
     * @param string $token
     * @return SalesChannelContext
     */
    public function load(string $token): SalesChannelContext
    {
        $context = $this->contextService->get(
            $this->salesContext->getSalesChannel()->getId(),
            $token,
            $this->salesContext->getSalesChannel()->getLanguageId()
        );
        $this->salesContext = $context;

        return $context;
    }
}
