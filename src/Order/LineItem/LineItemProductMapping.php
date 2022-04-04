<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem;

use Shopgate\Shopware\Order\LineItem\Events\AfterIncItemMappingEvent;
use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCartItem;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateExternalOrderItem;
use ShopgateLibraryException;
use ShopgateOrderItem;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductOutOfStockError;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LineItemProductMapping
{
    private ExtendedClassFactory $extendedClassFactory;
    private ContextManager $contextManager;
    private TaxMapping $taxMapping;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ContextManager $contextManager,
        ExtendedClassFactory $extendedClassFactory,
        EventDispatcherInterface $eventDispatcher,
        TaxMapping $taxMapping
    ) {
        $this->contextManager = $contextManager;
        $this->extendedClassFactory = $extendedClassFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->taxMapping = $taxMapping;
    }

    /**
     * @param ShopgateOrderItem[] $items
     * @return array
     */
    public function mapIncomingItems(array $items): array
    {
        $lineItems = [];
        foreach ($items as $item) {
            $dataBag = new DataBag([
                'id' => $item->getItemNumber(),
                'referencedId' => $item->getItemNumber(),
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'quantity' => (int)$item->getQuantity(),
                'stackable' => true
            ]);
            $this->eventDispatcher->dispatch(new AfterIncItemMappingEvent($dataBag, $item));
            $lineItems[] = $dataBag->all();
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
        $outgoingItem = $this->extendedClassFactory->createCartItem()->transformFromOrderItem($incomingItem);
        $outgoingItem->setItemNumber($lineItem->getId());
        $outgoingItem->setIsBuyable(1);
        $outgoingItem->setStockQuantity($lineItem->getQuantity());
        if ($delivery = $lineItem->getDeliveryInformation()) {
            $outgoingItem->setStockQuantity($delivery->getStock());
        }
        if ($price = $lineItem->getPrice()) {
            $outgoingItem->setQtyBuyable($price->getQuantity());
            [$priceWithTax, $priceWithoutTax] = $this->taxMapping->calculatePrices($price, $taxStatus);
            $outgoingItem->setUnitAmountWithTax($priceWithTax);
            $outgoingItem->setUnitAmount($priceWithoutTax);

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
        $errorItem = $this->extendedClassFactory->createCartItem()->transformFromOrderItem($missingItem);
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

    public function mapOutgoingOrderProduct(
        OrderLineItemEntity $swLineItem,
        ?string $taxStatus
    ): ShopgateExternalOrderItem {
        $sgLineItem = $this->extendedClassFactory->createOrderLineItem();
        $sgLineItem->setName($swLineItem->getLabel());
        $sgLineItem->setUnitAmount($swLineItem->getUnitPrice());
        $sgLineItem->setQuantity($swLineItem->getQuantity());
        $sgLineItem->setCurrency($this->contextManager->getSalesContext()->getCurrency()->getIsoCode());
        if ($price = $swLineItem->getPrice()) {
            [$priceWithTax, $priceWithoutTax] = $this->taxMapping->calculatePrices($price, $taxStatus);
            $sgLineItem->setUnitAmount($priceWithoutTax);
            $sgLineItem->setUnitAmountWithTax($priceWithTax);
            $sgLineItem->setTaxPercent($this->taxMapping->getPriceTaxRate($price));
        }

        /**
         * Deleted products will not have a 'product' reference
         */
        if ($product = $swLineItem->getProduct()) {
            $sgLineItem->setItemNumberPublic($product->getProductNumber());
            $sgLineItem->setItemNumber($swLineItem->getProductId());
            $sgLineItem->setName($product->getTranslation('name') ?: $product->getName());
            $sgLineItem->setDescription($product->getTranslation('description') ?: $product->getDescription());
        } else {
            $sgLineItem->setItemNumberPublic($swLineItem->getPayload()['productNumber'] ?? $swLineItem->getLabel());
            $sgLineItem->setItemNumber($swLineItem->getId());
        }

        return $sgLineItem;
    }
}
