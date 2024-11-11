<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\File;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;

/**
 * Monolog in 3.3.1 (SW6.5.2.0) does not have dateFormat in constructor.
 * Can remove this fallback class when the SW6 min version is switched to 6.5.4.0
 */
class ExtendedRotatingFileHandler extends RotatingFileHandler
{
    public function __construct(
        string $customDateTime,
        string $filename,
        int $maxFiles = 0,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
        ?int $filePermission = null,
        bool $useLocking = false,
    ) {
        parent::__construct($filename, $maxFiles, $level, $bubble, $filePermission, $useLocking);
        $this->dateFormat = $customDateTime;
    }
}
