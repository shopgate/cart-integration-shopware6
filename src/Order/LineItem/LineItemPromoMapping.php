<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem;

use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalCoupon;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Formatter;
use ShopgateExternalCoupon;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class LineItemPromoMapping
{
    private ContextManager $contextManager;
    private ExtendedClassFactory $classFactory;
    private TaxMapping $taxMapping;
    private Formatter $formatter;
    private int $shippingDiscountIndex = 1;

    public function __construct(
        ContextManager $contextManager,
        ExtendedClassFactory $classFactory,
        TaxMapping $taxMapping,
        Formatter $formatter
    ) {
        $this->contextManager = $contextManager;
        $this->classFactory = $classFactory;
        $this->taxMapping = $taxMapping;
        $this->formatter = $formatter;
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

    /**
     * A rule or coupon can have multiple discounts, and therefore we use discount ID as true identifier
     */
    public function mapValidCoupon(LineItem $lineItem, ExtendedCart $sgCart): ShopgateExternalCoupon
    {
        $discountId = $lineItem->getPayload()['discountId'] ?? $lineItem->getId();
        $couponCode = $lineItem->getReferencedId(); // empty string when automatic cart_rule
        $code = empty($couponCode) ? $discountId : $couponCode;
        $coupon = $sgCart->findExternalCoupon($code) ?? $this->classFactory->createExternalCoupon()->setIsNew(true);
        $coupon->setCode($code);
        $coupon->setType(empty($couponCode) ? ExtendedExternalCoupon::TYPE_CART_RULE
            : ExtendedExternalCoupon::TYPE_COUPON);
        $coupon->addDecodedInfo(array_intersect_key($lineItem->getPayload(),
            array_flip(['discountId', 'promotionId'])));
        $coupon->setCurrency($this->contextManager->getSalesContext()->getCurrency()->getIsoCode());
        $coupon->setIsValid(true);
        $coupon->setName($lineItem->getLabel());
        $coupon->setIsFreeShipping(false);
        if ($lineItem->getPrice()) {
            // might need to pass cart status instead
            [$priceWithTax, $priceWithoutTax] = $this->taxMapping->calculatePrices($lineItem->getPrice(), 'gross');
            $coupon->setAmountNet(-($priceWithoutTax));
            $coupon->setAmountGross(-($priceWithTax));
        }

        return $coupon;
    }

    public function mapOutgoingOrderPromo(OrderLineItemEntity $lineItem, ?string $taxStatus): ShopgateExternalCoupon
    {
        $id = $lineItem->getPayload()['promotionId'] ?? $lineItem->getId();
        $code = $lineItem->getReferencedId(); // empty string when automatic cart_rule
        $coupon = $this->classFactory->createExternalCoupon();
        $coupon->setIsValid(true);
        $coupon->setCode(empty($code) ? $id : $code);
        $coupon->setName($lineItem->getLabel());
        $coupon->setType(empty($code) ? ExtendedExternalCoupon::TYPE_CART_RULE : ExtendedExternalCoupon::TYPE_COUPON);
        $coupon->addDecodedInfo(array_intersect_key($lineItem->getPayload(),
            array_flip(['discountId', 'promotionId'])));
        $coupon->setCurrency($this->contextManager->getSalesContext()->getCurrency()->getIsoCode());

        if ($price = $lineItem->getPrice()) {
            [$priceWithTax, $priceWithoutTax] = $this->taxMapping->calculatePrices($price, $taxStatus);
            $coupon->setAmountNet(-($priceWithoutTax));
            $coupon->setAmountGross(-($priceWithTax));
        }
        $coupon->mergeInternalInfos();

        return $coupon;
    }

    public function mapOutgoingOrderShippingPromo(OrderDeliveryEntity $deliveryEntity, ?string $taxStatus)
    {
        $index = (string)$this->shippingDiscountIndex;
        $sgCoupon = $this->classFactory->createExternalCoupon();
        $sgCoupon->setIsValid(true);
        $sgCoupon->setCurrency($this->contextManager->getSalesContext()->getCurrency()->getIsoCode());
        $sgCoupon->setName(
            $this->formatter->translate('sg-quote.discountLabelShippingCosts', [], null) . " $index"
        );
        $sgCoupon->setCode($index);
        if ($price = $deliveryEntity->getShippingCosts()) {
            [$priceWithTax, $priceWithoutTax] = $this->taxMapping->calculatePrices($price, $taxStatus);
            $sgCoupon->setAmountNet(-($priceWithoutTax));
            $sgCoupon->setAmountGross(-($priceWithTax));
        }
        $this->shippingDiscountIndex++;

        return $sgCoupon;
    }
}
