<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Mapping;

use ShopgateLibraryException;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;

class QuoteErrorMapping
{
    /**
     * @param InvalidCartException $error
     * @return ShopgateLibraryException
     */
    public function mapInvalidCartError(InvalidCartException $error): ShopgateLibraryException
    {
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
     * @param ConstraintViolationException $exception
     * @return ShopgateLibraryException
     */
    public function mapConstraintError(ConstraintViolationException $exception): ShopgateLibraryException
    {
        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            (string) $exception->getViolations(),
            true
        );
    }

    /**
     * @param array $data
     * @return bool|string
     * @noinspection PhpComposerExtensionStubsInspection
     */
    private function toJson(array $data)
    {
        return extension_loaded('json') ? json_encode($data) : print_r($data, true);
    }
}
