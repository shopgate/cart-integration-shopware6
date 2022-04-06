<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterCheckCartEvent extends Event
{
    private array $result;
    private SalesChannelContext $context;

    public function __construct(array $result, SalesChannelContext $context)
    {
        $this->context = $context;
        $this->result = $result;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): void
    {
        $this->result = $result;
    }
}
