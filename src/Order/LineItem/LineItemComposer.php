<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\LineItem;

use Shopgate\Shopware\Order\LineItem\Events\AfterOutLineItemMappingEvent;
use Shopgate\Shopware\Order\LineItem\Events\BeforeIncLineItemMappingEvent;
use Shopgate\Shopware\Order\LineItem\Events\BeforeOutLineItemMappingEvent;
use Shopgate\Shopware\Order\Quote\QuoteBridge;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateCartBase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionNotEligibleError;
use Shopware\Core\Checkout\Promotion\Cart\Error\PromotionNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\ProductOutOfStockError;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LineItemComposer
{
    private LineItemProductMapping $productMapping;
    private LineItemPromoMapping $promoMapping;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private QuoteBridge $quoteBridge;

    /**
     * @param LineItemProductMapping $productMapping
     * @param LineItemPromoMapping $promoMapping
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $eventDispatcher
     * @param QuoteBridge $quoteBridge
     */
    public function __construct(
        LineItemProductMapping $productMapping,
        LineItemPromoMapping $promoMapping,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        QuoteBridge $quoteBridge
    ) {
        $this->productMapping = $productMapping;
        $this->promoMapping = $promoMapping;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->quoteBridge = $quoteBridge;
    }

    /**
     * @param ExtendedCart|ExtendedOrder $cart
     * @return array
     */
    public function mapIncomingLineItems(ShopgateCartBase $cart): array
    {
        $this->eventDispatcher->dispatch(new BeforeIncLineItemMappingEvent($cart));
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
        $externalCoupons = $sgCart->getExternalCoupons();
        $this->eventDispatcher->dispatch(new BeforeOutLineItemMappingEvent($cart, $sgCart));
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
                    $coupon = $this->promoMapping->mapValidCoupon($lineItem, $sgCart);
                    if ($coupon->isNew()) {
                        // otherwise it is updated by reference instead
                        $externalCoupons[$id] = $coupon;
                    }
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
                    $missingCoupon->setNotValidMessage($error->getMessage());
                    break;
                case PromotionNotEligibleError::class:
                    if (!$ineligibleCoupon = $sgCart->findExternalCouponByName($error->getParameters()['name'])) {
                        $this->logger->debug('Issue locating coupon by name: ' . $error->getParameters()['name']);
                        break;
                    }
                    $ineligibleCoupon->setNotValidMessage($error->getMessage());
                    break;
                default:
                    $this->logger->debug('Unmapped cart errors & notifications');
                    $this->logger->debug(print_r($error->jsonSerialize(), true));
            }
        }

        $dataBag = new DataBag([
            'items' => $lineItems,
            'external_coupons' => $externalCoupons
        ]);

        $this->eventDispatcher->dispatch(new AfterOutLineItemMappingEvent($dataBag));

        return $dataBag->all();
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

    /**
     * @param Cart $shopwareCart
     * @param SalesChannelContext $context
     * @param array $lineItems
     * @return Cart
     */
    public function addLineItemsToCart(Cart $shopwareCart, SalesChannelContext $context, array $lineItems): Cart
    {
        $request = new Request();
        $request->request->set('items', $lineItems);

        return $this->quoteBridge->addLineItemToQuote($request, $shopwareCart, $context);
    }
}