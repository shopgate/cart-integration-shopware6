<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Installers;

use Shopgate\Shopware\System\Db\Shipping\GenericDeliveryTime;

class DeliveryTimeInstaller extends EntityInstaller
{
    protected array $entityInstallList = [GenericDeliveryTime::class];
    protected string $entityName = 'delivery_time';
}
