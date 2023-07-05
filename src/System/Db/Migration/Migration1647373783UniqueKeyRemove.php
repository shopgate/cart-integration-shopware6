<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopgate\Shopware\Shopgate\ApiCredentials\ShopgateApiCredentialsDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1647373783UniqueKeyRemove extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1647373783;
    }

    /**
     * A Merchant can have multiple interfaces, the interfaces reuse
     * customerNumber & apiKey. Only shopNumber is actually unique.
     *
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        /** @noinspection SqlResolve */
        $sql = <<<SQL
            drop index sg_api_credentials_unique_apiKey on `#table_name#`;
            drop index sg_api_credentials_unique_customerNumber on `#table_name#`;
        SQL;
        $query = str_replace('#table_name#', ShopgateApiCredentialsDefinition::ENTITY_NAME, $sql);
        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }
}
