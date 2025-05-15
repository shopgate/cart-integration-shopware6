<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Rule;

use Shopgate\Shopware\System\Db\ClassCastInterface;
use Shopgate\Shopware\System\Db\EntityChecker;
use Shopware\Core\Content\Rule\RuleEntity;

$hasType = EntityChecker::checkPropertyHasType();

if (!$hasType) {
    class IsShopgateRuleGroup extends RuleEntity implements ClassCastInterface
    {
        public const UUID = '7d24818ee04546d797cb6fc1a604a379';
        protected $id = self::UUID;
        protected $name = 'Is Shopgate';
        protected $description = 'Check if the call is from Shopgate mobile API';
        protected $priority = 90;

        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'priority' => $this->priority,
                'conditions' => $this->conditions
            ];
        }
    }
} else {
    class IsShopgateRuleGroup extends RuleEntity implements ClassCastInterface
    {
        public const UUID = '7d24818ee04546d797cb6fc1a604a379';

        protected string $id = self::UUID;
        protected string $name = 'Is Shopgate';
        protected ?string $description = 'Check if the call is from Shopgate mobile API';
        protected int $priority = 90;

        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'priority' => $this->priority,
                'conditions' => $this->conditions
            ];
        }
    }
}

