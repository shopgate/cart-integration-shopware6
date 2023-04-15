<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Shopgate\Shopware\Shopgate\Salutations\ShopgateSalutationDefinition;
use ShopgateCustomer;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Salutation\SalutationDefinition;

class Migration1681060637Salutations extends MigrationStep
{

    public function getCreationTimestamp(): int
    {
        return 1681060637;
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        create table if not exists #table_name#
        (
            id                          BINARY(16)   not null comment 'Unique ID',
            sw_salutation_id            BINARY(16)   not null comment 'Mapping of Shopware salutation to SGs',
            value                       VARCHAR(1)   null comment 'Mapping of SG SDK constant',
            created_at                  DATETIME     default CURRENT_TIMESTAMP not null,
            updated_at                  DATETIME,
            constraint shopgate_go_salutations_pk primary key (id),
            constraint shopgate_go_salutations_value_uniq unique (value),
            constraint shopgate_go_salutations_salutation_id_fk
                foreign key (sw_salutation_id) references salutation (id)
                    on update cascade on delete cascade
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        comment 'Salutation mapping of Shopware to Shopgate SDK';
        SQL;
        $query = str_replace('#table_name#', ShopgateSalutationDefinition::ENTITY_NAME, $sql);
        $connection->executeStatement($query);

        // insert default mappings
        $map = [
            'mr' => ShopgateCustomer::MALE,
            'mrs' => ShopgateCustomer::FEMALE,
            'not_specified' => ShopgateCustomer::DIVERSE
        ];
        if ($result = $this->getExistingSalutations($connection)) {
            $mappedResults = array_filter($result, fn(array $row) => array_search($row['value'], $map));
            $mappedValues = array_map(fn(array $row) => $row['value'], $mappedResults);
            $unmapped = array_diff($map, $mappedValues);
            $unmappedResults = array_filter($result, fn(array $row) => $row['value'] === null);
            foreach ($unmappedResults as $row) {
                if ($key = $unmapped[$row['salutation_key']] ?? false) {
                    $connection->insert(ShopgateSalutationDefinition::ENTITY_NAME,
                        ['id' => Uuid::randomBytes(), 'sw_salutation_id' => $row['id'], 'value' => $key]);
                }
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }

    /**
     * @param Connection $connection
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getExistingSalutations(Connection $connection): array
    {
        $builder = $connection->createQueryBuilder();
        return $builder->select('swS.id, swS.salutation_key, sgS.value')
            ->from(SalutationDefinition::ENTITY_NAME, 'swS')
            ->leftJoin('swS',
                ShopgateSalutationDefinition::ENTITY_NAME, 'sgS', 'swS.id = sgS.sw_salutation_id')
            ->execute()
            ->fetchAllAssociative();
    }
}
