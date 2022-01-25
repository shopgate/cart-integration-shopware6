<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyFormatter;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Language\SalesChannel\AbstractLanguageRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class Formatter
{
    private ContextManager $contextManager;
    private TranslatorInterface $translator;
    private AbstractLanguageRoute $languageRoute;
    private ?LanguageCollection $languageCollection = null;
    private CurrencyFormatter $currencyFormatter;
    /** @var string|false|null */
    private $locale = false;

    public function __construct(
        ContextManager $contextManager,
        TranslatorInterface $translator,
        AbstractLanguageRoute $languageRoute,
        CurrencyFormatter $currencyFormatter
    ) {
        $this->contextManager = $contextManager;
        $this->translator = $translator;
        $this->languageRoute = $languageRoute;
        $this->currencyFormatter = $currencyFormatter;
    }

    /**
     * @throws MissingContextException
     */
    public function translate(string $key, array $parameters, ?string $domain = 'storefront'): string
    {
        $result = $this->translator->trans($key, $parameters, $domain, $this->getLocaleCode());

        return $result === $key ? '' : $result;
    }

    /**
     * @throws MissingContextException
     */
    public function getLocaleCode(): ?string
    {
        if (false === $this->locale) {
            /** @var LanguageEntity|null $entity */
            $entity = $this->getLanguageCollection()
                ->filterByProperty('id', $this->contextManager->getSalesContext()->getSalesChannel()->getLanguageId())
                ->first();
            $localeEntity = $entity ? $entity->getTranslationCode() : null;
            $this->locale = $localeEntity ? $localeEntity->getCode() : null;
        }

        return $this->locale;
    }

    /**
     * @throws MissingContextException
     */
    public function getLanguageCollection(): LanguageCollection
    {
        if (null === $this->languageCollection) {
            $this->languageCollection = $this->languageRoute->load(
                new Request(),
                $this->contextManager->getSalesContext(),
                (new Criteria())->addAssociation('language')
            )->getLanguages();
        }

        return $this->languageCollection;
    }

    /**
     * @throws MissingContextException
     * @see \Shopware\Core\Framework\Adapter\Twig\Filter\CurrencyFilter::formatCurrency()
     */
    public function formatCurrency(float $price): string
    {
        $channel = $this->contextManager->getSalesContext()->getSalesChannel();
        $context = $this->contextManager->getSalesContext()->getContext();
        $currency = $channel->getCurrency() ? $channel->getCurrency()->getIsoCode() : 'EUR';

        return $this->currencyFormatter->formatCurrencyByLanguage(
            $price,
            $currency,
            $context->getLanguageId(),
            $context
        );
    }

    /**
     * Converts 'packUnit' to 'Pack Unit'
     *
     * @param string $property
     * @return null|string
     */
    public function camelCaseToSpaced(string $property): ?string
    {
        return preg_replace('/(?<!^)([A-Z])/', ' \\1', ucfirst($property));
    }
}
