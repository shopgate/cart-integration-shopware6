<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Flysystem;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Shopgate_Model_Abstract;
use Shopgate_Model_XmlResultObject;
use ShopgateFileBufferXml;

class XmlFileBufferExtended extends ShopgateFileBufferXml
{
    private FilesystemInterface $privateFilesystem;

    public function __construct(
        Shopgate_Model_Abstract $xmlModel,
        Shopgate_Model_XmlResultObject $xmlNode,
        $capacity,
        FilesystemInterface $filesystem,
        $convertEncoding = true,
        array $sourceEncodings = array()
    ) {
        parent::__construct($xmlModel, $xmlNode, $capacity, $convertEncoding, $sourceEncodings);
        $this->privateFilesystem = $filesystem;
    }

    /**
     * Opens memory file handle
     *
     * @inheritDoc
     */
    public function setFile($filePath): void
    {
        $this->filePath = $filePath;
        $this->buffer = [];
        if (empty($this->fileHandle)) {
            $this->fileHandle = fopen('php://temp', 'wb');
        }
    }

    /**
     * @throws FileNotFoundException
     */
    public function getMeta(): array
    {
        if (!$this->privateFilesystem->has($this->filePath)) {
            return [];
        }

        return $this->privateFilesystem->getMetadata($this->filePath);
    }

    /**
     * Writes stream to file at the end
     *
     * @inheritDoc
     */
    protected function onFinish(): void
    {
        parent::onFinish();
        $this->privateFilesystem->putStream($this->filePath, $this->fileHandle);
    }
}
