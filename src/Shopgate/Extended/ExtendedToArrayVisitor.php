<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateContainerToArrayVisitor;

class ExtendedToArrayVisitor extends ShopgateContainerToArrayVisitor
{
    protected function sanitizeSimpleVar($v): mixed
    {
        return $v;
    }
}
