<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended\Flysystem;

interface ExtendedFileBufferInterface
{
    public function getFileSize(): int;
    public function getFilePath(): string;
}
