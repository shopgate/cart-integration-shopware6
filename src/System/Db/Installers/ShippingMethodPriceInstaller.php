<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\Shipping\FreeShippingMethodPrice;

class ShippingMethodPriceInstaller extends EntityInstaller
{
    protected array $entityInstallList = [FreeShippingMethodPrice::class];
    protected string $entityName = 'shipping_method_price';
}
