<?php

namespace Shopgate\Shopware\Storefront;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Holds our context for DI usage
 */
class ContextManager
{
    /** @var SalesChannelContext|null */
    private $salesContext;

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
}
