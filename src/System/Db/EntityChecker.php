<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db;

use Shopware\Core\Content\Rule\RuleEntity;

class EntityChecker
{
    public static function checkPropertyHasType(): bool {
        $reflectionClass = new \ReflectionClass(RuleEntity::class);
        if ($reflectionClass->hasProperty('id')) {
            $property = $reflectionClass->getProperty('id');
            return $property->hasType(); // Returns true if type is defined, false otherwise
        }
        return false;
    }
}
