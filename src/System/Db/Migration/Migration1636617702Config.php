<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1636617702Config extends MigrationStep
{

    public function getCreationTimestamp(): int
    {
        return 1636617702;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            UPDATE system_config
            SET
                configuration_key = REPLACE(configuration_key,
                    'ShopgateModule.',
                    'SgateShopgatePluginSW6.')
            WHERE
                configuration_key like '%ShopgateModule.%';
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }
}
