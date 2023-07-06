<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Flysystem;

use League\Flysystem\FilesystemException;
use Shopgate_Model_AbstractExport;
use Shopgate_Model_XmlResultObject;
use ShopgateFileBufferXml;
use Shopware\Core\Framework\Adapter\Filesystem\PrefixFilesystem;

class XmlFileBufferExtended extends ShopgateFileBufferXml implements ExtendedFileBufferInterface
{
    private PrefixFilesystem $privateFilesystem;

    public function __construct(
        Shopgate_Model_AbstractExport $xmlModel,
        Shopgate_Model_XmlResultObject $xmlNode,
        $capacity,
        PrefixFilesystem $filesystem,
        $convertEncoding = true,
        array $sourceEncodings = []
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
     * @throws FilesystemException
     */
    public function getFileSize(): int
    {
        if (!$this->privateFilesystem->has($this->filePath)) {
            return 0;
        }
        return $this->privateFilesystem->fileSize($this->filePath);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Writes stream to file at the end
     *
     * @inheritDoc
     */
    protected function onFinish(): void
    {
        parent::onFinish();
        $this->privateFilesystem->writeStream($this->filePath, $this->fileHandle);
    }
}
