<?php

namespace Shopgate\Shopware\System\Rule;

use Shopgate\Shopware\System\Di\Facade;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\Type;

class IsShopgateRule extends Rule
{
    public const UUID = 'b344814108424254b7c5147b2020f77e';
    public const RULE_NAME = 'is_shopgate';
    /** @var bool */
    protected $isShopgate = false;


    public function getName(): string
    {
        return self::RULE_NAME;
    }

    public function match(RuleScope $scope): bool
    {
        // Not implemented in this example
        $isShopgate = Facade::isInstantiated();

        // Checks if the shop administrator set the rule to "Is Shopgate => Yes"
        if ($this->isShopgate) {
            // Administrator wants the rule to match if a shopgate call.
            return $isShopgate;
        }

        // Shop administrator wants the rule to match if it's currently NOT a  shopgate call.
        return !$isShopgate;
    }

    public function getConstraints(): array
    {
        return [
            'isShopgate' => [ new Type('bool') ]
        ];
    }
}
