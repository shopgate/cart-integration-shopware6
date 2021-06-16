<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1623833835OrderVersion extends MigrationStep
{
    /**
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1623833835;
    }

    /**
     * Destructive updates do not run via admin plugin manager
     *
     * @param Connection $connection
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        DROP TABLE shopgate_order;
        CREATE TABLE `shopgate_order` (
            `id` BINARY(16) NOT NULL COMMENT 'Entity ID',
            `sw_order_id` BINARY(16) NOT NULL COMMENT 'Shopware Order Id',
            `sw_order_version_id` BINARY(16) NOT NULL COMMENT 'Shopware Order Version Ref',
            `sales_channel_id` BINARY(16) DEFAULT NULL COMMENT 'SalesChannel Id',
            `shopgate_order_number` VARCHAR(20) NOT NULL COMMENT 'Shopgate order number',
            `is_paid` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is paid',
            `is_sent` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is sent to shopgate',
            `is_cancellation_sent` TINYINT(1) NOT NULL COMMENT 'Is cancellation sent to shopgate',
            `is_test` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Is a test order',
            `received_data` text DEFAULT NULL COMMENT 'Received data',
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`shopgate_order_number`),
            UNIQUE `uniq.id` (`id`),
            KEY `SHOPGATE_ORDER_ORDER_ID` (`sw_order_id`),
            KEY `SHOPGATE_ORDER_STORE_ID` (`sales_channel_id`),
            CONSTRAINT `SHOPGATE_ORDER_ORDER_ID_SALES_ORDER_ENTITY_ID` FOREIGN KEY (`sw_order_id`, `sw_order_version_id`) REFERENCES `order` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `SHOPGATE_ORDER_STORE_ID_STORE_STORE_ID` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Shopgate Orders' COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // destructive updates
    }
}
