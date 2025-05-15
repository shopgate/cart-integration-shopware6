<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use ReflectionClass;
use ReflectionException;
use Shopgate\Shopware\Customer\CustomerBridge;
use Shopgate\Shopware\System\CurrencyComposer;
use Shopgate_Model_Catalog_TierPrice;
use Shopware\Core\Checkout\Cart\Rule\AlwaysValidRule;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Customer\Rule\CustomerGroupRule;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use Shopware\Core\Framework\Rule\Rule;

class TierPriceMapping
{

    public function __construct(
        private readonly CustomerBridge $customerBridge,
        private readonly CurrencyComposer $currencyComposer,
        private readonly PriceMapping $priceMapping
    ) {
    }

    /**
     * @returns Shopgate_Model_Catalog_TierPrice[]
     * @throws ReflectionException
     */
    public function mapTierPrices(ProductPriceCollection $priceCollection, Price $mainPrice): array
    {
        $groups = $this->customerBridge->getGroups();
        $list = [];
        foreach ($this->getValidTiers($priceCollection) as $swTier) {
            if (null === ($tierPrice = $this->mapProductTier($swTier, $mainPrice))) {
                continue;
            }
            /** @var AndRule|OrRule $payload */
            /** @noinspection NullPointerExceptionInspection */
            $payload = $swTier->getRule()->getPayload();
            $validGroupIds = $this->getConditionalCustomerGroups($payload, $groups);
            if ($validGroupIds) {
                foreach ($validGroupIds as $groupId) {
                    $newTierPrice = clone $tierPrice;
                    $newTierPrice->setCustomerGroupUid($groupId);
                    $list[] = $newTierPrice;
                }
            } else {
                $list[] = $tierPrice;
            }
        }

        return $list;
    }

    /**
     * Only returns tier price with valid rules
     *
     * @return ProductPriceEntity[]
     */
    private function getValidTiers(ProductPriceCollection $priceCollection): array
    {
        $validRules = [];
        foreach ($priceCollection as $swTier) {
            $rule = $swTier->getRule();
            if ($rule && $this->validateRule($rule)) {
                $validRules[] = $swTier;
            }
        }
        return $validRules;
    }

    private function validateRule(RuleEntity $rule): bool
    {
        $payload = $rule->getPayload();
        if (!$payload instanceof Rule) {
            return false;
        }

        return $this->isValidRule(true, $payload);
    }

    /**
     * Check if rule is valid for Shopgate export.
     * We consider valid if:
     * In AND condition only AlwaysValid or Group rule is present
     * In OR condition if one of AlwaysValid or Group rules are present
     */
    private function isValidRule(bool $carry, AndRule|OrRule|Rule $rule): bool
    {
        if ($rule instanceof OrRule || $rule instanceof AndRule) {
            return array_reduce($rule->getRules(), function ($carry, Rule $rule) {
                return $this->isValidRule($carry, $rule);
            }, $carry);
        }

        return $rule instanceof AlwaysValidRule || $rule instanceof CustomerGroupRule;
    }

    private function mapProductTier(
        ProductPriceEntity $priceEntity,
        Price $normalPrice
    ): ?Shopgate_Model_Catalog_TierPrice {
        $reducedPrice = $this->currencyComposer->extractCalculatedPrice($priceEntity->getPrice());
        if (!$reducedPrice) {
            return null;
        }
        $tierPrice = new Shopgate_Model_Catalog_TierPrice();
        $tierPrice->setFromQuantity($priceEntity->getQuantityStart());
        $tierPrice->setToQuantity($priceEntity->getQuantityEnd());
        $tierPrice->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
        $reduction = $this->priceMapping->mapPrice($normalPrice) - $this->priceMapping->mapPrice($reducedPrice);
        $tierPrice->setReduction($reduction);

        return $tierPrice;
    }

    /**
     * Retrieves CustomerGroup ID's that are valid from the passed rule container.
     * Does not handle AND, OR container logic.
     *
     * @throws ReflectionException
     */
    private function getConditionalCustomerGroups(
        AndRule|OrRule $ruleContainer,
        CustomerGroupCollection $groupCollection
    ): array {
        if (null !== $customerGroupRule = $this->findCustomerGroup(null, $ruleContainer)) {
            $reflection = new ReflectionClass(get_class($customerGroupRule));
            $grpProp = $reflection->getProperty('customerGroupIds');
            $opProp = $reflection->getProperty('operator');
            switch ($opProp->getValue($customerGroupRule)) {
                case '=':
                    return $grpProp->getValue($customerGroupRule);
                case '!=':
                    return array_diff($groupCollection->getIds(), $grpProp->getValue($customerGroupRule));
            }
        }

        return [];
    }

    private function findCustomerGroup(?CustomerGroupRule $carry, AndRule|OrRule|Rule $rule): ?CustomerGroupRule
    {
        if ($rule instanceof AndRule || $rule instanceof OrRule) {
            return array_reduce($rule->getRules(), function ($carry, Rule $item) {
                return $this->findCustomerGroup($carry, $item);
            }, $carry);
        }
        if ($rule instanceof CustomerGroupRule) {
            return $rule;
        }

        return $carry;
    }

    public function getHighestPrice(ProductPriceCollection $priceCollection, Price $basePrice): Price
    {
        return array_reduce(
            $this->getValidTiers($priceCollection),
            function (Price $carry, ProductPriceEntity $entity) {
                $curPrice = $this->currencyComposer->extractCalculatedPrice($entity->getPrice());
                if (!$curPrice) {
                    return $carry;
                }
                return $this->priceMapping->mapPrice($carry) > $this->priceMapping->mapPrice($curPrice)
                    ? $carry
                    : $curPrice;
            },
            $basePrice
        );
    }

    public function hasAlwaysValidRule(ProductPriceCollection $priceCollection): bool
    {
        return $priceCollection
                ->filter(fn(ProductPriceEntity $entity) => $entity->getRule() instanceof AlwaysValidRule)
                ->count() > 0;
    }

    /**
     * @param Shopgate_Model_Catalog_TierPrice[] $sgTierPrices
     */
    public function hasCustomerGroupRule(array $sgTierPrices, string $customerGroupId): bool
    {
        return count(array_filter(
                $sgTierPrices,
                fn(Shopgate_Model_Catalog_TierPrice $price) => $price->getCustomerGroupUid() === $customerGroupId
            )) > 0 ;
    }
}
