<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping;

use Shopware\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

/**
 * Use the trait for common logic between mappers
 */
trait MapTrait
{
    /**
     * @param SalesChannelEntity $channel
     * @param SeoUrlEntity|null $urlEntity
     * @return string
     */
    protected function getSeoUrl(SalesChannelEntity $channel, ?SeoUrlEntity $urlEntity): string
    {
        if (null === $urlEntity) {
            return '';
        }
        // intentional use of get, URL can be null which throws PHP invalid return type exception
        if (null !== $urlEntity->get('url')) {
            return $urlEntity->getUrl();
        }
        if (null === $channel->getDomains()) {
            return '';
        }
        $domainCollection = $channel->getDomains()->filterByProperty('languageId', $channel->getLanguageId());
        /** @var null|SalesChannelDomainEntity $domain */
        $domain = $domainCollection->count() ? $domainCollection->first() : $channel->getDomains()->first();
        $seoPath = ltrim($urlEntity->getSeoPathInfo() ?: $urlEntity->getPathInfo(), '/');
        return $domain && $domain->get('url') ? "{$domain->getUrl()}/{$seoPath}" : '';
    }
}
