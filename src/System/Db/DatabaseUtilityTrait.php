<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db;

use Doctrine\DBAL\ArrayParameterType;

/**
 * Utility trait for common database operations
 */
trait DatabaseUtilityTrait
{
    /**
     * Get the appropriate parameter type for binary data.
     * Uses BINARY if available, otherwise falls back to STRING.
     * This provides backward compatibility with different DBAL versions.
     */
    protected function getBinaryParameterType(): int
    {
        return defined('Doctrine\DBAL\ArrayParameterType::BINARY') 
            ? ArrayParameterType::BINARY 
            : ArrayParameterType::STRING;
    }
}