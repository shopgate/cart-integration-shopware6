<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote\Events;

use Shopware\Core\Checkout\Order\SalesChannel\OrderRouteResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AfterCustomerGetOrdersLoadEvent
{
    public function __construct(private readonly OrderRouteResponse $response, private readonly SalesChannelContext $context)
    {
    }

    public function getResponse(): OrderRouteResponse
    {
        return $this->response;
    }

    public function getContext(): SalesChannelContext
    {
        return $this->context;
    }
}
