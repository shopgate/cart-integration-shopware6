<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Shipping;

use Shopgate\Shopware\Order\State\StateBridge;
use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethod;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Symfony\Component\HttpFoundation\Request;

class ShippingBridge
{

    public function __construct(private readonly AbstractShippingMethodRoute $shippingMethodRoute, private readonly StateBridge $stateBridge)
    {
    }

    public function getShippingMethods(SalesChannelContext $context): ShippingMethodCollection
    {
        $criteria = (new Criteria())->addFilter(
            new NotFilter(
                MultiFilter::CONNECTION_OR,
                [new EqualsAnyFilter('id', [GenericShippingMethod::UUID, FreeShippingMethod::UUID])]
            )
        );
        $criteria->setTitle('shopgate::shipping-method::not-shopgate');
        return $this->shippingMethodRoute->load(
            new Request(['onlyAvailable' => true]),
            $context,
            $criteria
        )->getShippingMethods();
    }

    public function setOrderToShipped(string $deliveryId, SalesChannelContext $context): ?StateMachineStateEntity
    {
        return $this->stateBridge->transition(
            'order_delivery',
            $deliveryId,
            'ship',
            $context->getContext());
    }
}
