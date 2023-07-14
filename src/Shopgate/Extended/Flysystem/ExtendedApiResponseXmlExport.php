<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Flysystem;

use ShopgateLibraryException;

class ExtendedApiResponseXmlExport extends ExtendedPluginApiResponseXmlExport
{
    /**
     * Rewritten to save stream as data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * Rewritten to handle data stream
     * @throws ShopgateLibraryException
     */
    public function send(): void
    {
        if (is_resource($this->data)) {
            $fp = $this->data;
        } elseif (is_string($this->data)) {
            $fp = fopen($this->data, 'rb');
        } else {
            return;
        }

        // output headers ...
        header('HTTP/1.0 200 OK');
        $headers = $this->getHeaders();
        foreach ($headers as $header) {
            header($header);
        }

        // ... and the file
        while ($line = fgets($fp, 4096)) {
            echo $line;
        }

        // clean up and leave
        fclose($fp);
    }
}
