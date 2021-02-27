<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Rule;

use Shopgate\Shopware\Storefront\Controller\MainController;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\Type;

class IsShopgateRuleCondition extends Rule
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
        $isShopgate = defined(MainController::IS_SHOPGATE);

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
