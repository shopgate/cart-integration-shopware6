<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Flysystem;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use ShopgateFileBufferInterface;
use ShopgateLibraryException;
use ShopgatePluginApi;
use ShopgatePluginApiResponseAppJson;

class ExtendedPluginApi extends ShopgatePluginApi
{
    protected FilesystemInterface $privateFileSystem;
    protected ?ShopgateFileBufferInterface $buffer;

    public function setPrivateFileSystem(FilesystemInterface $filesystem): ExtendedPluginApi
    {
        $this->privateFileSystem = $filesystem;

        return $this;
    }

    /**
     * Needed to get name of the file to calculate file size for streams
     */
    public function setBuffer(ShopgateFileBufferInterface $buffer): ExtendedPluginApi
    {
        $this->buffer = $buffer;

        return $this;
    }

    public function handleRequest(array $data = array())
    {
        $origResponse = parent::handleRequest($data);

        if (!$this->buffer instanceof ExtendedFileBufferInterface || $origResponse->isError()) {
            return $origResponse;
        }

        try {
            $response = new ExtendedApiResponseXmlExport($this->trace_id);
            $response->setMeta($this->buffer->getMeta());
            $response->setData($this->privateFileSystem->readStream($origResponse->getBody()));
            return $response;
        } catch (FileNotFoundException $e) {
            throw new ShopgateLibraryException(ShopgateLibraryException::FILE_READ_WRITE_ERROR, $e->getMessage());
        }
    }
}
