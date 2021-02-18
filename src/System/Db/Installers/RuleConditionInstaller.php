<?php

namespace Shopgate\Shopware\System\Db\Installers;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;
use Shopgate\Shopware\System\Rule\IsShopgateRule;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

class RuleConditionInstaller
{
    /** @var ContainerInterface */
    private $connection;

    /**
     * @param ContainerInterface $container
     */
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
     * @throws DBALException
     */
    private function installRuleCondition(): void
    {
        $this->connection->insert('rule_condition', [
            'id' => Uuid::fromHexToBytes(IsShopgateRule::UUID),
            'type' => IsShopgateRule::RULE_NAME,
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
