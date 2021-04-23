<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Mapping;

use Shopgate\Shopware\System\Log\LoggerInterface;
use ShopgateLibraryException;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Throwable;

class QuoteErrorMapping
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param InvalidCartException $error
     * @return ShopgateLibraryException
     */
    public function mapInvalidCartError(InvalidCartException $error): ShopgateLibraryException
    {
        $this->logWithTrace($error);
        $errors = array_map(static function (Error $error) {
            return $error->jsonSerialize();
        }, $error->getCartErrors()->getElements());
        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            $this->toJson($errors),
            true
        );
    }

    /**
     * @param ShopwareHttpException $error
     */
    private function logWithTrace(ShopwareHttpException $error): void
    {
        $detailedErrors = [];
        foreach ($error->getErrors(true) as $detailedError) {
            $detailedErrors[] = $detailedError;
        }
        $this->logger->debug($this->toJson($detailedErrors));
    }

    /**
     * @param array $data
     * @return bool|string
     */
    private function toJson(array $data)
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        return extension_loaded('json') ? json_encode($data) : print_r($data, true);
    }

    /**
     * @param ConstraintViolationException $exception
     * @return ShopgateLibraryException
     */
    public function mapConstraintError(ConstraintViolationException $exception): ShopgateLibraryException
    {
        $this->logWithTrace($exception);

        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            (string)$exception->getViolations(),
            true
        );
    }

    /**
     * @param ShopwareHttpException $error
     * @return ShopgateLibraryException
     */
    public function mapGenericHttpException(ShopwareHttpException $error): ShopgateLibraryException
    {
        $this->logWithTrace($error);

        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            $error->getMessage(),
            true
        );
    }

    /**
     * @param Throwable $throwable
     * @return ShopgateLibraryException
     */
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
