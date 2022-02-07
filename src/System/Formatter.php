<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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
    /** @var string|false|null */
    private $locale = false;

    public function __construct(
        ContextManager $contextManager,
        TranslatorInterface $translator,
        AbstractLanguageRoute $languageRoute
    ) {
        $this->contextManager = $contextManager;
        $this->translator = $translator;
        $this->languageRoute = $languageRoute;
    }

    public function translate(string $key, array $parameters, ?string $domain = 'storefront'): string
    {
        $result = $this->translator->trans($key, $parameters, $domain, $this->getLocaleCode());

        return $result === $key ? '' : $result;
    }

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

    public function getLanguageCollection(): LanguageCollection
    {
        if (null === $this->languageCollection) {
            $criteria = (new Criteria())->addAssociation('language');
            $criteria->setTitle('shopgate::sales-channel-language');
            $this->languageCollection = $this->languageRoute->load(
                new Request(),
                $this->contextManager->getSalesContext(),
                $criteria
            )->getLanguages();
        }

        return $this->languageCollection;
    }

    /**
     * Converts 'packUnit' to 'Pack Unit'
     */
    public function camelCaseToSpaced(string $property): ?string
    {
        return preg_replace('/(?<!^)([A-Z])/', ' \\1', ucfirst($property));
    }
}
