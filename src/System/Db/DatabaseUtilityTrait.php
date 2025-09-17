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
    protected function getBinaryParameterType()
    {
        // Check if BINARY constant exists (for older DBAL versions with integer constants)
        if (defined('Doctrine\DBAL\ArrayParameterType::BINARY')) {
            return ArrayParameterType::BINARY;
        }
        
        // Fallback to STRING
        return ArrayParameterType::STRING;
    }
}