<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1730891295CategoryProductMapLangId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1730891295;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE `shopgate_go_category_product_mapping`');
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `shopgate_go_category_product_mapping` (
              `product_id` binary(16) NOT NULL,
              `category_id` binary(16) NOT NULL,
              `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT "Product position in category based on default sort",
              `sales_channel_id` BINARY(16) NOT NULL,
              `language_id` BINARY(16) NOT NULL,
              `product_version_id` binary(16) NOT NULL,
              `category_version_id` binary(16) NOT NULL,
              PRIMARY KEY (`product_id`,`product_version_id`,`category_id`,`category_version_id`, `sales_channel_id`, `language_id`),
              CONSTRAINT `fk.sg_category_product_mapping.product_id` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.sg_category_product_mapping.category_id` FOREIGN KEY (`category_id`, `category_version_id`) REFERENCES `category` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.sg_category_product_mapping.lang_id` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.sg_category_product_mapping.sales_channel_id` FOREIGN KEY (sales_channel_id) REFERENCES `sales_channel` (id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // no implementation
    }
}
