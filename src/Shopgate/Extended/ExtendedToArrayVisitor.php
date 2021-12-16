<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use ShopgateContainerToArrayVisitor;

class ExtendedToArrayVisitor extends ShopgateContainerToArrayVisitor
{
    /**
     * @return mixed
     */
    protected function sanitizeSimpleVar($v)
    {
        return $v;
    }
}
