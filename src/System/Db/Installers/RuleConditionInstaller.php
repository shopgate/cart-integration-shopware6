<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleCondition;
use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

class RuleConditionInstaller
{
    /** @var ContainerInterface */
    private $connection;

    public function __construct(ContainerInterface $container)
    {
        $this->connection = $container->get(Connection::class);
    }

    public function install(): void
    {
        try {
            $this->installRuleCondition();
        } catch (Throwable $throwable) {
            // can throw when db already has rule condition
        }
    }

    /**
     * @throws Exception
     */
    private function installRuleCondition(): void
    {
        $this->connection->insert('rule_condition', [
            'id' => Uuid::fromHexToBytes(IsShopgateRuleCondition::UUID),
            'type' => IsShopgateRuleCondition::RULE_NAME,
            'rule_id' => Uuid::fromHexToBytes(IsShopgateRuleGroup::UUID),
            'parent_id' => null,
            'value' => '{"isShopgate": true}',
            'position' => 0,
            'custom_fields' => null,
            'created_at' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'updated_at' => null
        ]);
    }
}
