<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopgate\Shopware\Shopgate\ApiCredentials\ShopgateApiCredentialsDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1659433798UniqueKeyRemoveTwo extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1659433798;
    }

    /**
     * A merchant may have 2 interfaces that use the same language
     * e.g. DE & AT stores both use German
     *
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            ALTER TABLE #table_name# DROP FOREIGN KEY sg_api_credentials_language_id_fk;
            ALTER TABLE #table_name# DROP FOREIGN KEY sg_api_credentials_sales_channel_id_fk;
            DROP INDEX sg_api_credentials_language_id_fk ON #table_name#;
            DROP INDEX sg_api_credentials_unique_channel_lang ON #table_name#;
            ALTER TABLE #table_name# ADD CONSTRAINT sg_api_credentials_sales_channel_id_fk foreign key (sales_channel_id) references sales_channel (id) ON DELETE CASCADE;
        SQL;
        $query = str_replace('#table_name#', ShopgateApiCredentialsDefinition::ENTITY_NAME, $sql);
        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }
}
