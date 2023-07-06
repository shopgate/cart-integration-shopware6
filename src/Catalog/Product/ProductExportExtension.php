<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product;

use Shopware\Core\Framework\Struct\Struct;

class ProductExportExtension extends Struct
{
    public const EXT_KEY = 'shopgate_internal_info';

    public function __construct(protected array $data)
    {
    }

    public function __toString(): string
    {
        $encoded = json_encode($this->getVars());

        return (string)$encoded;
    }
}
