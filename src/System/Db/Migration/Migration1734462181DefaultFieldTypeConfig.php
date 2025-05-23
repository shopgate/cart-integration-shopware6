<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use JsonException;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

use const JSON_THROW_ON_ERROR;

class Migration1734462181DefaultFieldTypeConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1734462181;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $configPresent = $connection->fetchOne(
            'SELECT 1 FROM `system_config` WHERE `configuration_key` = ?',
            [ConfigBridge::SYSTEM_CONFIG_PROD_PROP_DOMAIN]
        );
        if ($configPresent !== false) {
            // Already configured, don't alter the setting
            return;
        }
        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => ConfigBridge::SYSTEM_CONFIG_PROD_PROP_DOMAIN,
            'configuration_value' => json_encode([
                '_value' => [
                    [
                        'value' => 'product',
                        'label' => 'Products',
                    ]
                ]
            ], JSON_THROW_ON_ERROR),
            'created_at' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
