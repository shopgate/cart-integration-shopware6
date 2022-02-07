<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\System\Currency\CurrencyFormatter;

class CurrencyComposer
{
    private CurrencyFormatter $currencyFormatter;
    private ContextManager $contextManager;
    private CashRounding $rounding;

    public function __construct(
        CurrencyFormatter $currencyFormatter,
        CashRounding $rounding,
        ContextManager $contextManager
    ) {
        $this->currencyFormatter = $currencyFormatter;
        $this->contextManager = $contextManager;
        $this->rounding = $rounding;
    }

    /**
     * @see \Shopware\Core\Framework\Adapter\Twig\Filter\CurrencyFilter::formatCurrency()
     */
    public function formatCurrency(float $price): string
    {
        $channel = $this->contextManager->getSalesContext();
        $currency = $channel->getCurrency() ? $channel->getCurrency()->getIsoCode() : 'EUR';

        return $this->currencyFormatter->formatCurrencyByLanguage(
            $price,
            $currency,
            $channel->getContext()->getLanguageId(),
            $channel->getContext()
        );
    }

    /**
     * GetCurrencyPrice has a fallback to main price of the SW.
     * This is a check that a fallback did not happen. If it did,
     * that means the merchant did not specify a price for this
     * currency. Which means we have to calculate.
     *
     * E.g. Price is 100 EUR. Currency factors EUR 1.0, USD 1.15,
     * the formula: 100*1.15 to get USD price.
     * @see PriceCollection::getCurrencyPrice()
     */
    public function toCalculatedPrice(Price $price): Price
    {
        $channel = $this->contextManager->getSalesContext();
        // currency matches, we do not need to calculate
        if ($price->getCurrencyId() === $channel->getCurrencyId()) {
            return $price;
        }
        $channelFactor = $channel->getCurrency()->getFactor();

        return new Price(
            $channel->getCurrencyId(),
            $this->roundAsItem($price->getNet() * $channelFactor),
            $this->roundAsItem($price->getGross() * $channelFactor),
            false
        );
    }

    public function extractCalculatedPrice(?PriceCollection $priceCollection): ?Price
    {
        if (null === $priceCollection) {
            return null;
        }
        $channel = $this->contextManager->getSalesContext();
        $price = $priceCollection->getCurrencyPrice($channel->getCurrencyId(), true);

        return $price ? $this->toCalculatedPrice($price) : null;
    }

    public function roundAsItem(float $value): float
    {
        return $this->rounding->cashRound(
            $value,
            $this->contextManager->getSalesContext()->getItemRounding()
        );
    }
}
