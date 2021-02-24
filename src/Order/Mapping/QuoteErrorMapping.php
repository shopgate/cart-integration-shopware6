<?php

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
        $elements = $error->getCartErrors()->getElements();
        return new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            print_r(array_map(static function (Error $error) {
                return $error->jsonSerialize();
            }, $elements), true),
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
}
