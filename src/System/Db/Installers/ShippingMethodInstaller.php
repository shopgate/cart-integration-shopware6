<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;

class ShippingMethodInstaller extends EntityChannelInstaller
{
    use EntityActivateTrait;

    protected $entityInstallList = [GenericShippingMethod::class];
    protected $entityName = 'shipping_method';
}
