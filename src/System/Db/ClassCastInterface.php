<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db;

interface ClassCastInterface
{
    public function getId(): string;

    /**
     * Outputs properties that need to be saved to Shopware database to array
     */
    public function toArray(): array;
}
