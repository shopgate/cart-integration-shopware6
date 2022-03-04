<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopgate\Shopware\Shopgate\ApiCredentials\ShopgateApiCredentialsDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1646325249ApiCredentials extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1646325249;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        create table #table_name# (
            id               binary(16)        not null COMMENT 'Entity ID',
            sales_channel_id binary(16)        not null COMMENT 'SalesChannel Id',
            language_id      binary(16)        not null COMMENT 'Language this config belongs to',
            active           tinyint           default 0 null COMMENT 'Is config active?',
            customer_number  int               not null,
            shop_number      int               not null,
            api_key           varchar(255)     not null,
            created_at       datetime          default CURRENT_TIMESTAMP not null,
            updated_at       datetime,
            constraint sg_api_credentials_pk primary key (id),
            constraint sg_api_credentials_pk_2 unique (sales_channel_id, language_id),
            constraint sg_api_credentials_pk_3 unique (shop_number),
            constraint sg_api_credentials_pk_4 unique (customer_number),
            constraint sg_api_credentials_pk_5 unique (api_key),
            constraint sg_api_credentials_language_id_fk 
                foreign key (language_id) references language (id) on delete cascade,
            constraint sg_api_credentials_sales_channel_id_fk
                foreign key (sales_channel_id) references sales_channel (id) on delete cascade
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        create unique index sg_api_credentials_id_uindex on #table_name# (id);
        SQL;
        $query = str_replace('#table_name#', ShopgateApiCredentialsDefinition::ENTITY_NAME, $sql);
        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }
}
