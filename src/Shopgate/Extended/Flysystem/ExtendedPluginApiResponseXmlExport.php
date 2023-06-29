<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Flysystem;

use ShopgatePluginApiResponseExport;

class ExtendedPluginApiResponseXmlExport extends ShopgatePluginApiResponseExport
{
    private int $size;
    private string $path;


    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        $fileName = str_replace('export/', '', $this->path);
        return [
            'Content-Type: application/xml',
            'Content-Length: ' . $this->size,
            'Content-Disposition: attachment; filename="' . basename($fileName) . '"'
        ];
    }
}
