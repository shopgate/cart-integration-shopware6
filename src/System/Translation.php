<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Language\SalesChannel\LanguageRoute;
use Symfony\Component\HttpFoundation\Request;

class Translation
{
    /** @var ContextManager */
    private $contextManager;
    /** @var Translator */
    private $translator;
    /** @var LanguageRoute */
    private $languageRoute;
    /** @var LanguageCollection|null */
    private $languageCollection;

    /**
     * @param ContextManager $contextManager
     * @param Translator $translator
     * @param LanguageRoute $languageRoute
     */
    public function __construct(ContextManager $contextManager, Translator $translator, LanguageRoute $languageRoute)
    {
        $this->contextManager = $contextManager;
        $this->translator = $translator;
        $this->languageRoute = $languageRoute;
    }

    /**
     * @param string $key
     * @param array $parameters
     * @param string|null $domain
     * @return string
     * @throws MissingContextException
     */
    public function translate(string $key, array $parameters, string $domain = 'storefront'): string
    {
        return $this->translator->trans($key, $parameters, $domain, $this->getLocaleCode());
    }

    /**
     * @return string|null
     * @throws MissingContextException
     */
    private function getLocaleCode(): ?string
    {
        /** @var LanguageEntity|null $entity */
        $entity = $this->getLanguageCollection()
            ->filterByProperty('id', $this->contextManager->getSalesContext()->getSalesChannel()->getLanguageId())
            ->first();
        $localeEntity = $entity ? $entity->getTranslationCode(): null;

        return $localeEntity ? $localeEntity->getCode() : null;
    }

    /**
     * @return LanguageCollection
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
}
