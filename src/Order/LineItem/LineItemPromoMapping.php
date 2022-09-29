<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem;

use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalCoupon;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Formatter;
use ShopgateExternalCoupon;
use ShopgateExternalOrderExternalCoupon;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
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
    public function mapValidCoupon(LineItem $lineItem, ExtendedCart $sgCart): ExtendedExternalCoupon
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
            $status = $this->contextManager->getSalesContext()->getTaxState();
            $this->applyOneCouponAmount($coupon, $lineItem->getPrice(), $status);
        }
        $coupon->mergeInternalInfos();

        return $coupon;
    }

    public function mapOutgoingOrderPromo(
        OrderLineItemEntity $lineItem,
        ?string $taxStatus
    ): ShopgateExternalOrderExternalCoupon {
        $id = $lineItem->getPayload()['promotionId'] ?? $lineItem->getId();
        $code = $lineItem->getReferencedId(); // empty string when automatic cart_rule
        $sgCoupon = $this->classFactory->createOrderExportCoupon();
        $sgCoupon->setCode(empty($code) ? $id : $code);
        $sgCoupon->setName($lineItem->getLabel());
        $sgCoupon->addDecodedInfo(array_merge(
            ['itemType' => empty($code) ? ExtendedExternalCoupon::TYPE_CART_RULE : ExtendedExternalCoupon::TYPE_COUPON],
            array_intersect_key($lineItem->getPayload(), array_flip(['discountId', 'promotionId']))
        ));
        $sgCoupon->setCurrency($this->contextManager->getSalesContext()->getCurrency()->getIsoCode());

        if ($price = $lineItem->getPrice()) {
            [$priceWithTax,] = $this->taxMapping->calculatePrices($price, $taxStatus);
            $sgCoupon->setAmount(abs($priceWithTax));
        }
        $sgCoupon->mergeInternalInfos();

        return $sgCoupon;
    }

    public function mapOutgoingOrderShippingPromo(
        OrderDeliveryEntity $deliveryEntity,
        ?string $taxStatus
    ): ShopgateExternalOrderExternalCoupon {
        $index = (string)$this->shippingDiscountIndex;
        $sgCoupon = $this->classFactory->createOrderExportCoupon();
        $sgCoupon->setCurrency($this->contextManager->getSalesContext()->getCurrency()->getIsoCode());
        $sgCoupon->setName(
            $this->formatter->translate('sg-quote.discountLabelShippingCosts', [], null) . " $index"
        );
        $sgCoupon->setCode($index);
        if ($price = $deliveryEntity->getShippingCosts()) {
            [$priceWithTax,] = $this->taxMapping->calculatePrices($price, $taxStatus);
            $sgCoupon->setAmount(abs($priceWithTax));
        }

        $this->shippingDiscountIndex++;

        return $sgCoupon;
    }

    /**
     * Only one amount should be set
     * @noinspection PhpParamsInspection
     */
    private function applyOneCouponAmount(ShopgateExternalCoupon $coupon, CalculatedPrice $amount, string $status): void
    {
        [$priceWithTax, $priceWithoutTax] = $this->taxMapping->calculatePrices($amount, $status);
        if ($status === CartPrice::TAX_STATE_GROSS) {
            $coupon->setAmountNet(null);
            $coupon->setAmountGross(abs($priceWithTax));
        } else {
            $coupon->setAmountGross(null);
            $coupon->setAmountNet(abs($priceWithoutTax));
        }
    }
}
