<?php

namespace Shopgate\Shopware\Components\Di;

use Shopgate\Shopware\Export\ExportService;

/**
 * Forwarder for Plugin class where we cannot use
 * DI via constructor because of the "final" constructor
 * in the SDK library class. Instantiated via Facade class.
 */
class Forwarder
{
    /** @var ExportService */
    private $exportService;

    /**
     * @param ExportService $exportService
     */
    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * @return ExportService
     */
    public function getExportService(): ExportService
    {
        return $this->exportService;
    }
}
