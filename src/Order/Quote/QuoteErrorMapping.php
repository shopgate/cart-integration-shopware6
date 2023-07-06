<?php declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote;

use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Throwable;

class QuoteErrorMapping
{

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function mapInvalidCartError(InvalidCartException $error): ShopgateLibraryException
    {
        $this->logWithTrace($error);
        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            $error->getMessage(),
            true
        );
    }

    private function logWithTrace(ShopwareHttpException $error): void
    {
        $detailedErrors = [];
        foreach ($error->getErrors(true) as $detailedError) {
            $detailedErrors[] = $detailedError;
        }
        $this->logger->debug($detailedErrors);
    }

    public function mapConstraintError(ConstraintViolationException $exception): ShopgateLibraryException
    {
        $this->logWithTrace($exception);

        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            (string)$exception->getViolations(),
            true
        );
    }

    public function mapGenericHttpException(ShopwareHttpException $error): ShopgateLibraryException
    {
        $this->logWithTrace($error);

        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            $error->getMessage(),
            true
        );
    }

    public function mapThrowable(Throwable $throwable): ShopgateLibraryException
    {
        $error = "Message: {$throwable->getMessage()},
                    Location: {$throwable->getFile()}:{$throwable->getLine()}";
        $this->logger->error($error);
        $this->logger->debug($error . "\n" . $throwable->getTraceAsString());

        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            $throwable->getMessage(),
            true
        );
    }
}
