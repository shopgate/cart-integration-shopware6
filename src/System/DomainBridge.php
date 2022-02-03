<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System;

use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class DomainBridge
{
    private EntityRepositoryInterface $domainRepository;

    /**
     * @param EntityRepositoryInterface $domainRepository
     */
    public function __construct(EntityRepositoryInterface $domainRepository)
    {
        $this->domainRepository = $domainRepository;
    }

    /**
     * @param SalesChannelContext $context
     * @return string
     * @throws SalesChannelDomainNotFoundException
     */
    public function getDomain(SalesChannelContext $context): string
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()))
            ->setLimit(1);
        $criteria->setTitle('shopgate::domain::sales-channel-id');
        /** @var SalesChannelDomainEntity $domain */
        $domain = $this->domainRepository
            ->search($criteria, $context->getContext())
            ->first();

        if (!$domain) {
            throw new SalesChannelDomainNotFoundException($context->getSalesChannel());
        }

        return $domain->getUrl();
    }
}
