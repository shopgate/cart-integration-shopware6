<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\Rule\IsShopgateRuleGroup;

class RuleInstaller extends EntityInstaller
{
    protected array $entityInstallList = [IsShopgateRuleGroup::class];
    protected string $entityName = 'rule';
}
