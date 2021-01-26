<?php

namespace Shopgate\Shopware\Components\Di;

use Shopgate\Shopware\Export\ExportService;
use Shopgate\Shopware\Export\ImportService;

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

    /**
     * @param ExportService $exportService
     * @param ImportService $importService
     */
    public function __construct(ExportService $exportService, ImportService $importService)
    {
        $this->exportService = $exportService;
        $this->importService = $importService;
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
}
