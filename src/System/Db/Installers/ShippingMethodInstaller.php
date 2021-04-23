<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethod;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;

class ShippingMethodInstaller extends EntityChannelInstaller
{
    use EntityActivateTrait;

    /**
     * Make sure to also exclude shipping methods from check_cart
     * @see \Shopgate\Shopware\Order\Mapping\ShippingMapping::mapShippingMethods()
     * @var string[]
     */
    protected $entityInstallList = [GenericShippingMethod::class, FreeShippingMethod::class];
    protected $entityName = 'shipping_method';
}
