<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping;

use Shopgate\Shopware\Order\Shipping\Events\AfterShippingMethodMappingEvent;
use Shopgate\Shopware\Order\Shipping\Events\BeforeDeliveryContextSwitchEvent;
use Shopgate\Shopware\Order\Shipping\Events\BeforeManualShippingPriceSet;
use Shopgate\Shopware\Order\Shipping\Events\BeforeShippingMethodMappingEvent;
use Shopgate\Shopware\Order\State\StateComposer;
use Shopgate\Shopware\Shopgate\Extended\ExtendedCart;
use Shopgate\Shopware\Shopgate\Extended\ExtendedOrder;
use Shopgate\Shopware\Storefront\ContextManager;
use ShopgateCartBase;
use ShopgateLibraryException;
use ShopgateShippingMethod;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryCalculator;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryProcessor;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity as ShippingMethod;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

class ShippingComposer
{

    public function __construct(
        private readonly ShippingBridge           $shippingBridge,
        private readonly ShippingMapping          $shippingMapping,
        private readonly CheckoutCartPageLoader   $cartPageLoader,
        private readonly StateComposer            $stateComposer,
        private readonly ContextManager           $contextManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string                   $shopwareVersion
    )
    {
    }

    /**
     * Adds manual shipping fee.
     * Make sure it's not 0.0 value. There is an issue with setting the
     * manual shipping cost to 0. This is why we need to use our custom
     * Free Shipping method in this case.
     *
     * @see DeliveryCalculator::calculateDelivery
     */
    public function addShippingFeeToCart(ExtendedCart|ExtendedOrder|ShopgateCartBase $quote, Cart $swCart): void
    {
        $shippingCost = $quote->getShippingCost($this->contextManager->getSalesContext()->getTaxState());
        $shopCurrency = $this->contextManager->getSalesContext()->getCurrencyId();
        // for some reason manual shipping cost calculator uses default shop currency
        if ($shopCurrency !== Defaults::CURRENCY && version_compare($this->shopwareVersion, '6.5.5.0', '<')) {
            $shippingCost /= $this->contextManager->getSalesContext()->getCurrency()->getFactor();
        }
        $price = new CalculatedPrice(
            $shippingCost,
            $shippingCost,
            $swCart->getShippingCosts()->getCalculatedTaxes(),
            $swCart->getShippingCosts()->getTaxRules()
        );
        $this->eventDispatcher->dispatch(new BeforeManualShippingPriceSet($price, $swCart, $quote));
        $swCart->addExtension(DeliveryProcessor::MANUAL_SHIPPING_COSTS, $price);
    }

    /**
     * @param SalesChannelContext $context
     * @return ShopgateShippingMethod[]
     * @throws ShopgateLibraryException
     */
    public function mapOutgoingShipping(SalesChannelContext $context): array
    {
        $deliveries = $this->getCalculatedDeliveries($context);
        $this->eventDispatcher->dispatch(new BeforeShippingMethodMappingEvent($deliveries));
        $result = new DataBag($deliveries->map(
            fn(Delivery $delivery) => $this->shippingMapping->mapOutCartShippingMethod($delivery))
        );
        $this->eventDispatcher->dispatch(new AfterShippingMethodMappingEvent($result));

        return $result->all();
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function getCalculatedDeliveries(SalesChannelContext $context): DeliveryCollection
    {
        $list = [];
        $request = new Request();
        $request->setSession(new Session()); // support for 3rd party plugins that do not check session existence

        $methods = $this->shippingBridge->getShippingMethods($context);
        $this->sortSelectedShipping($methods, $context);

        try {
            foreach ($methods->getElements() as $shipMethod) {
                $dataBag = new RequestDataBag([SalesChannelContextService::SHIPPING_METHOD_ID => $shipMethod->getId()]);
                $this->eventDispatcher->dispatch(new BeforeDeliveryContextSwitchEvent($dataBag));
                $resultContext = $this->contextManager->switchContext($dataBag, $context)->getSalesContext();
                $cart = $this->cartPageLoader->load($request, $resultContext)->getCart();
                $deliveries = $this->sortCartDeliveries($cart->getDeliveries());
                $delivery = $deliveries->first();
                // we have shipping discounts
                if ($delivery && $deliveries->count() > 1) {
                    $cost = $this->combineShippingCost($deliveries);
                    $delivery->setShippingCosts($cost);
                }
                if ($delivery) {
                    $list[$delivery->getShippingMethod()->getId()] = $delivery;
                }
            }
        } catch (Throwable $throwable) {
            if (strpos($throwable->getMessage(), 'LanguageEntity')) {
                throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                    'No SaleChannel domain exists corresponding to the SaleChannel default language', true);
            }
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $throwable->getMessage());
        }

        return new DeliveryCollection($list);
    }

    public function mapIncomingShipping(ExtendedCart|ExtendedOrder|ShopgateCartBase $quote, SalesChannelContext $context): string
    {
        return $this->shippingMapping->getShopwareShippingId($quote, $context->getTaxState());
    }

    public function isFullyShipped(?OrderDeliveryCollection $deliveries): bool
    {
        $delivery = $this->getFirstShippingDelivery($deliveries);

        return $delivery && $this->stateComposer->isFullyShipped($delivery->getStateMachineState());
    }

    public function isCancelled(?OrderDeliveryCollection $deliveries): bool
    {
        $delivery = $this->getFirstShippingDelivery($deliveries);

        return $delivery && $this->stateComposer->isCancelled($delivery->getStateMachineState());
    }

    /**
     * We assume that it's not multi-shipping
     *
     * @see sortOrderDeliveries
     */
    public function getFirstShippingDelivery(?OrderDeliveryCollection $deliveries): ?OrderDeliveryEntity
    {
        return $deliveries ? $this->sortOrderDeliveries($deliveries)->first() : null;
    }

    /**
     * Sorts deliveries in way where the first delivery is supposedly the
     * actual "shipping" price (>0 value), the rest are shipping discounts.
     * NB! This may not be accounting for multi-shipping.
     */
    public function sortOrderDeliveries(OrderDeliveryCollection $deliveries): OrderDeliveryCollection
    {
        $deliveries->sort(
            function (OrderDeliveryEntity $one, OrderDeliveryEntity $two) {
                return $two->getShippingCosts() <=> $one->getShippingCosts();
            }
        );

        return $deliveries;
    }

    /**
     * @see sortOrderDeliveries for detailed explanation
     */
    public function sortCartDeliveries(DeliveryCollection $deliveries): DeliveryCollection
    {
        $deliveries->sort(
            function (Delivery $one, Delivery $two) {
                return $two->getShippingCosts() <=> $one->getShippingCosts();
            }
        );

        return $deliveries;
    }

    public function setToShipped(
        ?OrderDeliveryCollection $deliveries,
        SalesChannelContext      $context
    ): ?StateMachineStateEntity
    {
        $delivery = $this->getFirstShippingDelivery($deliveries);

        return $delivery ? $this->shippingBridge->setOrderToShipped($delivery->getId(), $context) : null;
    }

    /**
     * Combines shipping discounts into one price.
     * Note that we do not combine tax numbers well.
     */
    private function combineShippingCost(DeliveryCollection $deliveries): CalculatedPrice
    {
        return new CalculatedPrice(
            $deliveries->reduce(static function (float $result, Delivery $delivery) {
                return $result + $delivery->getShippingCosts()->getUnitPrice();
            }, 0.0),
            $deliveries->reduce(static function (float $result, Delivery $delivery) {
                return $result + $delivery->getShippingCosts()->getTotalPrice();
            }, 0.0),
            $deliveries->first()->getShippingCosts()->getCalculatedTaxes(),
            $deliveries->first()->getShippingCosts()->getTaxRules()
        );
    }

    /**
     * Sort by position as defined by SW v6.4.11.0+
     * Add default & currently selected to the end of the list
     */
    private function sortSelectedShipping(ShippingMethodCollection $collection, SalesChannelContext $context): void
    {
        if (method_exists(ShippingMethod::class, 'getPosition')) {
            $collection->sort(fn(ShippingMethod $x, ShippingMethod $y) => $x->getPosition() <=> $y->getPosition());
        }
        $ids = array_reverse(array_merge(
            [
                $context->getShippingMethod()->getId() => $context->getShippingMethod()->getId(),
                $context->getSalesChannel()->getShippingMethodId() => $context->getSalesChannel()->getShippingMethodId()
            ],
            $collection->getIds()
        ));

        $collection->sortByIdArray($ids);
    }
}
