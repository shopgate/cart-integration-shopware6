<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Core;

use ShopgateLibraryException;
use ShopgatePluginApiResponseAppXmlExport;

class ExtendedApiResponseXmlExport extends ShopgatePluginApiResponseAppXmlExport
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
     *
     * @throws ShopgateLibraryException
     */
    public function send(): void
    {
        $this->assertData($this->data);

        if (is_resource($this->data)) {
            $fp = $this->data;
        } else {
            $fp = @fopen($this->data, 'rb');
            $this->assertData($fp);
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
        exit;
    }

    /**
     * @throws ShopgateLibraryException
     */
    private function assertData($stream): void
    {
        if (!$stream) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_FILE_OPEN_ERROR,
                'File: ' . $this->data,
                true
            );
        }
    }
}
