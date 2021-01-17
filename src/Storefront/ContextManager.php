<?php

namespace Shopgate\Shopware\Storefront;

use Shopware\Core\Framework\Context as FrameworkContext;

/**
 * Holds our context for DI usage
 */
class ContextManager
{
    /**
     * @var FrameworkContext|null
     */
    private $apiContext;

    /**
     * @param FrameworkContext $context
     * @return ContextManager
     */
    public function setApiContext(FrameworkContext $context): ContextManager
    {
        $this->apiContext = $context;

        return $this;
    }

    /**
     * @return FrameworkContext|null
     */
    public function getApiContext(): ?FrameworkContext
    {
        return $this->apiContext;
    }
}
