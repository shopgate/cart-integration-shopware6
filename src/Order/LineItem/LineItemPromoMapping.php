<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem;

use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalCoupon;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateExternalCoupon;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class LineItemPromoMapping
{
    private ContextManager $contextManager;
    private ExtendedClassFactory $classFactory;
    private TaxMapping $taxMapping;

    public function __construct(
        ContextManager $contextManager,
        ExtendedClassFactory $classFactory,
        TaxMapping $taxMapping
    ) {
        $this->contextManager = $contextManager;
        $this->classFactory = $classFactory;
        $this->taxMapping = $taxMapping;
    }

    /**
     * @param ExtendedExternalCoupon[] $promos
     * @return array
     */
    public function mapIncomingPromos(array $promos): array
    {
        $lineItems = [];
        foreach ($promos as $coupon) {
            // skip adding line item as cart rules are applied automatically
            if ($coupon->getType() === ExtendedExternalCoupon::TYPE_CART_RULE) {
                continue;
            }
            $lineItems[] = [
                'referencedId' => $coupon->getCode(),
                'type' => LineItem::PROMOTION_LINE_ITEM_TYPE
            ];
        }

        return $lineItems;
    }

    public function mapValidCoupon(LineItem $lineItem, ExtendedCart $sgCart): ShopgateExternalCoupon
    {
        $couponId = $lineItem->getPayload()['promotionId'] ?? $lineItem->getId();
        $couponCode = $lineItem->getReferencedId(); // empty string when automatic cart_rule
        $code = empty($couponCode) ? $couponId : $couponCode;
        $coupon = $sgCart->findExternalCoupon($code) ?? $this->classFactory->createExternalCoupon()->setIsNew(true);
        $coupon->setCode($code);
        $coupon->setType(empty($couponCode) ? ExtendedExternalCoupon::TYPE_CART_RULE
            : ExtendedExternalCoupon::TYPE_COUPON);
        $coupon->addDecodedInfo(['id' => $couponId]);
        $coupon->setCurrency($this->contextManager->getSalesContext()->getCurrency()->getIsoCode());
        $coupon->setIsValid(true);
        $coupon->setName($lineItem->getLabel());
        $coupon->setIsFreeShipping(false);
        if ($lineItem->getPrice()) {
            $coupon->setAmountGross(-($lineItem->getPrice()->getTotalPrice()));
        }

        return $coupon;
    }

    public function mapOutgoingOrderPromo(OrderLineItemEntity $lineItem, ?string $taxStatus): ShopgateExternalCoupon
    {
        $id = $lineItem->getPayload()['promotionId'] ?? $lineItem->getId();
        $code = $lineItem->getReferencedId(); // empty string when automatic cart_rule
        $sgCoupon = $this->classFactory->createExternalCoupon();
        $sgCoupon->setIsValid(true);
        $sgCoupon->setCode(empty($code) ? $id : $code);
        $sgCoupon->setName($lineItem->getLabel());
        $sgCoupon->setType(empty($code) ? ExtendedExternalCoupon::TYPE_CART_RULE
            : ExtendedExternalCoupon::TYPE_COUPON);
        $sgCoupon->setCurrency($this->contextManager->getSalesContext()->getCurrency()->getIsoCode());

        if ($price = $lineItem->getPrice()) {
            [$priceWithTax, $priceWithoutTax] = $this->taxMapping->calculatePrices($price, $taxStatus);
            $sgCoupon->setAmountNet(-($priceWithoutTax));
            $sgCoupon->setAmountGross(-($priceWithTax));
        }

        return $sgCoupon;
    }
}
