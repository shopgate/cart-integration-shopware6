<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterCheckCartEvent extends Event
{

    public function __construct(private array $result, private readonly SalesChannelContext $context)
    {
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
