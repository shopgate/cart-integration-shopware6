<?php declare(strict_types=1);

namespace Shopgate\Shopware\Customer;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractUpsertAddressRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AddressBridge
{
    private AbstractUpsertAddressRoute $upsertAddressRoute;
    private EntityRepository $addressRepository;

    public function __construct(
        AbstractUpsertAddressRoute $upsertAddressRoute,
        EntityRepository $addressRepository
    ) {
        $this->upsertAddressRoute = $upsertAddressRoute;
        $this->addressRepository = $addressRepository;
    }

    public function addAddress(
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        CustomerEntity $customer
    ): CustomerAddressEntity {
        return $this->upsertAddressRoute->upsert(null, $dataBag, $context, $customer)->getAddress();
    }

    public function updateAddress($data, SalesChannelContext $context): void
    {
        $this->addressRepository->update($data, $context->getContext());
    }
}
