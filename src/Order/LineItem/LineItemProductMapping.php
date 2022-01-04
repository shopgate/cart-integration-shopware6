<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem;

use Shopgate\Shopware\Shopgate\Extended\ExtendedCartItem;
use ShopgateLibraryException;
use ShopgateOrderItem;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductOutOfStockError;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;

class LineItemProductMapping
{
    /**
     * @param ShopgateOrderItem[] $items
     * @return array
     */
    public function mapIncomingProducts(array $items): array
    {
        $lineItems = [];
        foreach ($items as $item) {
            $lineItems[] = [
                'id' => $item->getItemNumber(),
                'referencedId' => $item->getItemNumber(),
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'quantity' => (int)$item->getQuantity(),
                'stackable' => true
            ];
        }

        return $lineItems;
    }

    /**
     * Valid products are the ones that are still in Shopware cart
     * after all validation checks are made
     */
    public function mapValidProduct(
        LineItem $lineItem,
        ShopgateOrderItem $incomingItem,
        ErrorCollection $collection,
        string $taxStatus
    ): ExtendedCartItem {
        $outgoingItem = (new ExtendedCartItem())->transformFromOrderItem($incomingItem);
        $outgoingItem->setItemNumber($lineItem->getId());
        $outgoingItem->setIsBuyable(1);
        $outgoingItem->setStockQuantity($lineItem->getQuantity());
        if ($delivery = $lineItem->getDeliveryInformation()) {
            $outgoingItem->setStockQuantity($delivery->getStock());
        }
        if ($price = $lineItem->getPrice()) {
            $outgoingItem->setQtyBuyable($price->getQuantity());
            //todo: test items with custom tax entered instead of %
            /** @var CalculatedTax $tax */
            $tax = array_reduce(
                $price->getCalculatedTaxes()->getElements(),
                static function (float $carry, CalculatedTax $tax) {
                    return $carry + $tax->getTax();
                },
                0.0
            );
            if ($taxStatus === 'net') {
                $outgoingItem->setUnitAmount($price->getUnitPrice());
                $outgoingItem->setUnitAmountWithTax($price->getUnitPrice() + ($tax / $price->getQuantity()));
            } else {
                $outgoingItem->setUnitAmountWithTax($price->getUnitPrice());
                $outgoingItem->setUnitAmount($price->getUnitPrice() - ($tax / $price->getQuantity()));
            }

            /**
             * Soft line item errors that do not remove items from the cart
             */
            if ($errors = $this->getProductErrors($collection, $lineItem->getId())) {
                $text = '';
                foreach ($errors as $error) {
                    $text .= $error->getMessage() . '. ';
                    if ($error instanceof ProductStockReachedError) {
                        $outgoingItem->setIsBuyable(0);
                        $outgoingItem->setError(
                            ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE
                        );
                        // adjust quantity to call requested as Shopware adjusts to the correct max. qty possible
                        $outgoingItem->setStockQuantity($incomingItem->getQuantity());
                    }
                }
                $outgoingItem->setErrorText($text);
            }
        }

        return $outgoingItem;
    }

    /**
     * @return Error[]
     */
    private function getProductErrors(ErrorCollection $errors, string $lineItemId): array
    {
        return array_filter($errors->getElements(), function (Error $error) use ($lineItemId) {
            $id = $this->getIdFromError($error);
            return $id === $lineItemId;
        });
    }

    public function getIdFromError(Error $error): string
    {
        return str_replace($error->getMessageKey(), '', $error->getId());
    }

    /**
     * Invalid products that are removed from cart by Shopware
     */
    public function mapInvalidProduct(Error $error, ShopgateOrderItem $missingItem): ExtendedCartItem
    {
        $errorItem = (new ExtendedCartItem())->transformFromOrderItem($missingItem);
        $errorItem->setIsBuyable(0);
        $errorItem->setErrorText(sprintf($error->getMessage(), $missingItem->getName()));
        if ($error instanceof ProductNotFoundError) {
            $errorItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND);
        } elseif ($error instanceof ProductOutOfStockError) {
            $errorItem->setError(ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK);
        } else {
            $errorItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_ALLOWED);
        }

        return $errorItem;
    }
}
