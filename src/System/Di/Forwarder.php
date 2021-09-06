<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Di;

use Shopgate\Shopware\ExportService;
use Shopgate\Shopware\ImportService;
use Shopgate\Shopware\Shopgate\RequestPersist;
use Shopgate\Shopware\System\Log\LoggerInterface;

/**
 * Forwarder for Plugin class where we cannot use
 * DI via constructor because of the "final" constructor
 * in the SDK library class. Instantiated via Facade class.
 */
class Forwarder
{
    private ExportService $exportService;
    private ImportService $importService;
    private LoggerInterface $logger;
    private RequestPersist $requestPersist;

    public function __construct(
        ExportService $exportService,
        ImportService $importService,
        LoggerInterface $logger,
        RequestPersist $requestPersist
    ) {
        $this->exportService = $exportService;
        $this->importService = $importService;
        $this->logger = $logger;
        $this->requestPersist = $requestPersist;
    }

    public function getExportService(): ExportService
    {
        return $this->exportService;
    }

    public function getImportService(): ImportService
    {
        return $this->importService;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getRequestPersist(): RequestPersist
    {
        return $this->requestPersist;
    }
}
