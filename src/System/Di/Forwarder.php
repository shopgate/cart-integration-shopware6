<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Di;

use Shopgate\Shopware\ExportService;
use Shopgate\Shopware\ImportService;
use Shopgate\Shopware\System\Log\LoggerInterface;

/**
 * Forwarder for Plugin class where we cannot use
 * DI via constructor because of the "final" constructor
 * in the SDK library class. Instantiated via Facade class.
 */
class Forwarder
{
    /** @var ExportService */
    private $exportService;
    /** @var ImportService */
    private $importService;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ExportService $exportService
     * @param ImportService $importService
     */
    public function __construct(ExportService $exportService, ImportService $importService, LoggerInterface $logger)
    {
        $this->exportService = $exportService;
        $this->importService = $importService;
        $this->logger = $logger;
    }

    /**
     * @return ExportService
     */
    public function getExportService(): ExportService
    {
        return $this->exportService;
    }

    /**
     * @return ImportService
     */
    public function getImportService(): ImportService
    {
        return $this->importService;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
