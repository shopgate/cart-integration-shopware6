<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Core;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use ShopgateLibraryException;
use ShopgatePluginApi;
use ShopgatePluginApiResponseAppXmlExport;

class ExtendedPluginApi extends ShopgatePluginApi
{
    protected FilesystemInterface $privateFileSystem;

    public function setPrivateFileSystem(FilesystemInterface $filesystem): ExtendedPluginApi
    {
        $this->privateFileSystem = $filesystem;

        return $this;
    }

    /**
     * Is needed for XML export calls
     */
    public function setPreventResponse(bool $prevent): ExtendedPluginApi
    {
        $this->preventResponseOutput = $prevent;

        return $this;
    }

    /**
     * @throws ShopgateLibraryException
     */
    public function handleRequest(array $data = array())
    {
        parent::handleRequest($data);

        // Only runs if preventResponseOutput was set to true
        if ($this->response instanceof ShopgatePluginApiResponseAppXmlExport) {
            $this->response = new ExtendedApiResponseXmlExport($this->trace_id);
        }
        try {
            $this->response->setData(
                $this->privateFileSystem->readStream($this->responseData)
            );
        } catch (FileNotFoundException $e) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_FILE_OPEN_ERROR,
                'File: ' . $this->responseData,
                true
            );
        }

        $this->response->send();
    }

    protected function getCategories(): void
    {
        parent::getCategories();
        $this->setPreventResponse(true);
    }

    protected function getItems(): void
    {
        parent::getItems();
        $this->setPreventResponse(true);
    }

    protected function getReviews(): void
    {
        parent::getReviews();
        $this->setPreventResponse(true);
    }
}
