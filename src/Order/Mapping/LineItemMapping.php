<?php

namespace Shopgate\Shopware\Order\Mapping;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCartItem;
use ShopgateCartBase;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductOutOfStockError;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;

class LineItemMapping
{
    /**
     * @param ShopgateCartBase $cart
     * @return array
     */
    public function mapIncomingLineItems(ShopgateCartBase $cart): array
    {
        $lineItems = [];
        foreach ($cart->getItems() as $item) {
            $lineItems[] = [
                'id' => $item->getItemNumber(),
                'referencedId' => $item->getItemNumber(),
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'quantity' => (int)$item->getQuantity()
            ];
        }
        foreach ($cart->getExternalCoupons() as $coupon) {
            $lineItems[] = [
                'referencedId' => $coupon->getCode(),
                'type' => LineItem::PROMOTION_LINE_ITEM_TYPE
            ];
        }

        return $lineItems;
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
        /** @var LineItem $lineItem */
        foreach ($cart->getLineItems() as $id => $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $incomingItem = $sgCart->findItemById($id);
                if (null === $incomingItem) {
                    continue;
                }
                $sgCartItem = (new ExtendedCartItem())->transformFromOrderItem($incomingItem);
                $sgCartItem->setItemNumber($id);
                $sgCartItem->setIsBuyable(1);
                $sgCartItem->setStockQuantity($lineItem->getQuantity());
                if ($delivery = $lineItem->getDeliveryInformation()) {
                    $sgCartItem->setStockQuantity($delivery->getStock());
                }
                if ($price = $lineItem->getPrice()) {
                    $sgCartItem->setQtyBuyable($price->getQuantity());
                    $sgCartItem->setUnitAmountWithTax(round($price->getUnitPrice(), 2));

                    /** @var CalculatedTax $tax */
                    $tax = array_reduce(
                        $price->getCalculatedTaxes()->getElements(),
                        static function (float $carry, CalculatedTax $tax) {
                            return $carry + $tax->getTax();
                        },
                        0.0
                    );
                    $sgCartItem->setUnitAmount(round($price->getUnitPrice() - ($tax / $price->getQuantity()), 2));

                    if ($errors = $this->getProductErrors($cart, $id)) {
                        $text = '';
                        foreach ($errors as $error) {
                            $text .= $error->getMessage() . '. ';
                            if ($error instanceof ProductStockReachedError) {
                                $sgCartItem->setIsBuyable(0);
                                $sgCartItem->setError(
                                    ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE
                                );
                                $sgCartItem->setStockQuantity($incomingItem->getQuantity());
                            }
                        }
                        $sgCartItem->setErrorText($text);
                    }
                }
                $lineItems[$id] = $sgCartItem;
            } elseif ($lineItem->getType() === LineItem::PROMOTION_LINE_ITEM_TYPE) {
                $sgPromoItem = $sgCart->findExternalCoupon($lineItem->getReferencedId());
                if (null === $sgPromoItem) {
                    continue;
                }
                $sgPromoItem->setIsValid(true);
                $sgPromoItem->setName($lineItem->getLabel());
                $sgPromoItem->setIsFreeShipping(false);
                if ($lineItem->getPrice()) {
                    $sgPromoItem->setAmountGross(-($lineItem->getPrice()->getTotalPrice()));
                }
                $sgPromoItem->setCurrency($sgCart->getCurrency()); // note the use
                $externalCoupons[$id] = $sgPromoItem;
            }
        }

        //todo: move
        /** @var Error $error */
        foreach ($cart->getErrors() as $error) {
            if ($error instanceof ProductNotFoundError) {
                $missingItem = $sgCart->findItemById($error->getParameters()['id']);
                if (!$missingItem) {
                    continue;
                }
                $errorCartItem = (new ExtendedCartItem())->transformFromOrderItem($missingItem);
                $errorCartItem->setIsBuyable(0);
                $errorCartItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND);
                $errorCartItem->setErrorText(sprintf($error->getMessage(), $missingItem->getName()));
                $lineItems[$errorCartItem->getItemNumber()] = $errorCartItem;
            } elseif ($error instanceof ProductOutOfStockError) {
                $id = $this->getIdFromError($error);
                $missingItem = $sgCart->findItemById($id);
                if (!$missingItem) {
                    continue;
                }
                $errorCartItem = (new ExtendedCartItem())->transformFromOrderItem($missingItem);
                $errorCartItem->setIsBuyable(0);
                $errorCartItem->setError(ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK);
                $errorCartItem->setErrorText(sprintf($error->getMessage(), $missingItem->getName()));
                $lineItems[$errorCartItem->getItemNumber()] = $errorCartItem;
            } elseif ($error instanceof PromotionNotFoundError) {
                $missingCoupon = $sgCart->findExternalCoupon($error->getParameters()['code']);
                if (!$missingCoupon) {
                    continue;
                }
                $missingCoupon->setIsValid(false);
                $missingCoupon->setNotValidMessage($error->getMessage());
                $externalCoupons[$error->getParameters()['code']] = $missingCoupon;
            }
        }

        return [
            'items' => $lineItems,
            'external_coupons' => $externalCoupons
        ];
    }

    /**
     * @param Cart $cart
     * @param $lineItemId
     * @return Error[]
     */
    private function getProductErrors(Cart $cart, $lineItemId): array
    {
        return array_filter($cart->getErrors()->getElements(), function (Error $error) use ($lineItemId) {
            $id = $this->getIdFromError($error);
            return $id === $lineItemId;
        });
    }

    /**
     * @param Error $error
     * @return string
     */
    private function getIdFromError(Error $error): string
    {
        return str_replace($error->getMessageKey(), '', $error->getId());
    }
}
