<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethodPrice;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethodPrice;

class ShippingMethodPriceInstaller extends EntityInstaller
{
    protected array $entityInstallList = [FreeShippingMethodPrice::class, GenericShippingMethodPrice::class];
    protected string $entityName = 'shipping_method_price';
}
