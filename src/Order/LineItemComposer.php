<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order;

use Shopgate\Shopware\Order\Mapping\LineItem\LineItemProductMapping;
use Shopgate\Shopware\Order\Mapping\LineItem\LineItemPromoMapping;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateCartBase;
use ShopgateExternalCoupon;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionNotEligibleError;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductOutOfStockError;

class LineItemComposer
{
    private LineItemProductMapping $productMapping;
    private LineItemPromoMapping $promoMapping;
    private LoggerInterface $logger;

    /**
     * @param LineItemProductMapping $productMapping
     * @param LineItemPromoMapping $promoMapping
     * @param LoggerInterface $logger
     */
    public function __construct(
        LineItemProductMapping $productMapping,
        LineItemPromoMapping $promoMapping,
        LoggerInterface $logger
    ) {
        $this->productMapping = $productMapping;
        $this->promoMapping = $promoMapping;
        $this->logger = $logger;
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
            if (!$this->isValidUuid($id)) {
                $this->logger->debug('Invalid line item id provided: ' . $id);
                break;
            }
            switch ($lineItem->getType()) {
                case LineItem::PRODUCT_LINE_ITEM_TYPE:
                    $incomingItem = $sgCart->findItemById($id);
                    if (null === $incomingItem) {
                        $this->logger->debug('Cannot locate line item in sg cart: ' . $id);
                        break;
                    }
                    $lineItems[$id] = $this->productMapping->mapValidProduct(
                        $lineItem,
                        $incomingItem,
                        $cart->getErrors()
                    );
                    break;
                case LineItem::PROMOTION_LINE_ITEM_TYPE:
                    $refId = $lineItem->getReferencedId();
                    $sgPromoItem = $sgCart->findExternalCoupon($refId) ?? new ShopgateExternalCoupon();
                    $sgPromoItem->setCode(empty($refId) ? $id : $refId); // for cart_rule
                    $sgPromoItem->setInternalInfo(empty($refId) ? LineItemPromoMapping::RULE_ID : '');
                    $sgPromoItem->setCurrency($sgCart->getCurrency());
                    $externalCoupons[$id] = $this->promoMapping->mapValidCoupon($lineItem, $sgPromoItem);
                    break;
                default:
                    $this->logger->debug('Cannot map item type: ' . $lineItem->getType());
                    $this->logger->debug(print_r($lineItem->jsonSerialize(), true));
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
                    $id = $this->productMapping->getIdFromError($error);
                    if (!$missingItem = $sgCart->findItemById($id)) {
                        $this->logger->debug('Issue locating product: ' . $id);
                        break;
                    }
                    $exportItem = $this->productMapping->mapInvalidProduct($error, $missingItem);
                    $lineItems[$exportItem->getItemNumber()] = $exportItem;
                    break;
                case PromotionNotFoundError::class:
                    if (!$missingCoupon = $sgCart->findExternalCoupon($error->getParameters()['code'])) {
                        $this->logger->debug('Issue locating coupon by code: ' . $error->getParameters()['code']);
                        break;
                    }
                    $missingCoupon->setIsValid(false);
                    $missingCoupon->setNotValidMessage($error->getMessage());
                    $externalCoupons[$error->getParameters()['code']] = $missingCoupon;
                    break;
                case PromotionNotEligibleError::class:
                    if (!$ineligibleCoupon = $sgCart->findExternalCouponByName($error->getParameters()['name'])) {
                        $this->logger->debug('Issue locating coupon by name: ' . $error->getParameters()['name']);
                        break;
                    }
                    $ineligibleCoupon->setIsValid(false);
                    $ineligibleCoupon->setNotValidMessage($error->getMessage());
                    $externalCoupons[] = $ineligibleCoupon;
                    break;
                default:
                    $this->logger->debug('Unmapped cart errors & notifications');
                    $this->logger->debug(print_r($error->jsonSerialize(), true));
            }
        }

        return [
            'items' => $lineItems,
            'external_coupons' => $externalCoupons
        ];
    }

    /**
     * Safety check for strange cart cases with duplicate
     * line items without proper ID's
     * @param string $uuid
     * @return bool
     */
    private function isValidUuid(string $uuid): bool
    {
        return ctype_xdigit($uuid);
    }
}
