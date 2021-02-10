<?php

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Order\Mapping\LineItem\LineItemProductMapping;
use Shopgate\Shopware\Order\Mapping\LineItem\LineItemPromoMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use ShopgateCartBase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductOutOfStockError;

class LineItemComposer
{
    /** @var LineItemProductMapping */
    private $productMapping;
    /** @var LineItemPromoMapping */
    private $promoMapping;

    /**
     * @param LineItemProductMapping $productMapping
     * @param LineItemPromoMapping $promoMapping
     */
    public function __construct(LineItemProductMapping $productMapping, LineItemPromoMapping $promoMapping)
    {
        $this->productMapping = $productMapping;
        $this->promoMapping = $promoMapping;
    }

    /**
     * @param ShopgateCartBase $cart
     * @return array
     */
    public function mapIncomingLineItems(ShopgateCartBase $cart): array
    {
        return array_merge(
            $this->productMapping->mapIncomingProducts($cart->getItems()),
            $this->promoMapping->mapIncomingPromos($cart->getExternalCoupons())
        );
    }

    /**
     * @param Cart $cart
     * @param ExtendedCart $sgCart
     * @return array
     */
    public function mapOutgoingLineItems(Cart $cart, ExtendedCart $sgCart): array
    {
        $lineItems = [];
        $externalCoupons = [];
        /**
         * Handle line items that are still in cart after all validations
         */
        foreach ($cart->getLineItems() as $id => $lineItem) {
            switch ($lineItem->getType()) {
                case LineItem::PRODUCT_LINE_ITEM_TYPE:
                    $incomingItem = $sgCart->findItemById($id);
                    if (null === $incomingItem) {
                        break;
                    }
                    $lineItems[$id] = $this->productMapping->mapValidProduct(
                        $lineItem,
                        $incomingItem,
                        $cart->getErrors()
                    );
                    break;
                case LineItem::PROMOTION_LINE_ITEM_TYPE:
                    $sgPromoItem = $sgCart->findExternalCoupon($lineItem->getReferencedId());
                    if (null === $sgPromoItem) {
                        break;
                    }
                    $sgPromoItem->setCurrency($sgCart->getCurrency()); // note the use
                    $externalCoupons[$id] = $this->promoMapping->mapValidCoupon($lineItem, $sgPromoItem);
                    break;
            }
        }

        /**
         * Handle removed items because of errors
         */
        foreach ($cart->getErrors() as $error) {
            $errorClass = get_class($error);
            switch ($errorClass) {
                case ProductNotFoundError::class:
                case ProductOutOfStockError::class:
                    $missingItem = $sgCart->findItemById($this->productMapping->getIdFromError($error));
                    if (!$missingItem) {
                        break;
                    }
                    $exportItem = $this->productMapping->mapInvalidProduct($error, $missingItem);
                    $lineItems[$exportItem->getItemNumber()] = $exportItem;
                    break;
                case PromotionNotFoundError::class:
                    $missingCoupon = $sgCart->findExternalCoupon($error->getParameters()['code']);
                    if (!$missingCoupon) {
                        break;
                    }
                    $missingCoupon->setIsValid(false);
                    $missingCoupon->setNotValidMessage($error->getMessage());
                    $externalCoupons[$error->getParameters()['code']] = $missingCoupon;
                    break;
            }
        }

        return [
            'items' => $lineItems,
            'external_coupons' => $externalCoupons
        ];
    }
}
