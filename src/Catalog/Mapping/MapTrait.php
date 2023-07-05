<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopware\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Use the trait for common logic between mappers
 */
trait MapTrait
{
    protected function getSeoUrl(SalesChannelContext $context, ?SeoUrlEntity $urlEntity): string
    {
        if (null === $urlEntity) {
            return '';
        }
        // intentional use of get, URL can be null which throws PHP invalid return type exception
        if (null !== $urlEntity->get('url')) {
            return $urlEntity->getUrl();
        }
        $domains = $context->getSalesChannel()->getDomains();
        if (null === $domains) {
            return '';
        }
        $domainCollection = $domains->filterByProperty('languageId', $context->getContext()->getLanguageId());
        $domain = $domainCollection->count() ? $domainCollection->first() : $domains->first();
        $seoPath = ltrim($urlEntity->getSeoPathInfo() ?: $urlEntity->getPathInfo(), '/');

        return $domain && $domain->get('url') ? "{$domain->getUrl()}/$seoPath" : '';
    }
}
