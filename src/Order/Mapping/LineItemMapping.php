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
        /** @var LineItem $lineItem */
        foreach ($cart->getLineItems() as $id => $lineItem) {
            //todo - support other types
            $incomingItem = $sgCart->findItemById($id);
            if (null === $incomingItem || $lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }
            $sgCartItem = (new ExtendedCartItem())->transformFromOrderItem($incomingItem);
            $sgCartItem->setItemNumber($id);
            $sgCartItem->setIsBuyable(1);
            $sgCartItem->setQtyBuyable($lineItem->getQuantity());
            if ($delivery = $lineItem->getDeliveryInformation()) {
                $sgCartItem->setQtyBuyable($delivery->getStock());
            }
            if ($price = $lineItem->getPrice()) {
                $sgCartItem->setStockQuantity($price->getQuantity());
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
                        if ($error instanceof ProductOutOfStockError) {
                            $sgCartItem->setIsBuyable(0);
                            $sgCartItem->setError(ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK);
                        } elseif ($error instanceof ProductStockReachedError) {
                            $sgCartItem->setIsBuyable(0);
                            $sgCartItem->setError(ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE);
                            $sgCartItem->setStockQuantity($incomingItem->getQuantity());
                        }
                    }
                    $sgCartItem->setErrorText($text);
                }
            }
            $lineItems[$id] = $sgCartItem;
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
                $lineItems[] = $errorCartItem;
            }
        }

        return $lineItems;
    }

    /**
     * @param Cart $cart
     * @param $lineItemId
     * @return Error[]
     */
    private function getProductErrors(Cart $cart, $lineItemId): array
    {
        return array_filter($cart->getErrors()->getElements(), static function (Error $error) use ($lineItemId) {
            $id = ltrim($error->getId(), $error->getMessageKey());
            return $id === $lineItemId;
        });
    }

}
