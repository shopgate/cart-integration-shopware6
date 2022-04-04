<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Events;

use Symfony\Contracts\EventDispatcher\Event;

class AfterCheckCartEvent extends Event
{
    private array $result;

    /**
     * @param array $result
     */
    public function __construct(array $result)
    {
        $this->result = $result;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function setResult(array $result): void
    {
        $this->result = $result;
    }
}
