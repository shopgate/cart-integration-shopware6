<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\CustomField;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomFieldBridge
{
    private EntityRepositoryInterface $customFieldRepository;

    public function __construct(EntityRepositoryInterface $customFieldRepository)
    {
        $this->customFieldRepository = $customFieldRepository;
    }

    /**
     * @param string $type - 'customer', 'product', etc. See `custom_field_set_relation` table.
     * @param SalesChannelContext $salesChannelContext
     * @return EntitySearchResult
     */
    public function getFieldList(string $type, SalesChannelContext $salesChannelContext): EntitySearchResult
    {
        $criteria = (new Criteria())
            ->addFilter(new AndFilter([
                new EqualsFilter('active', true),
                new EqualsFilter('customFieldSet.active', true),
                new EqualsFilter('customFieldSet.relations.entityName', strtolower($type))
            ]))
            ->addAssociations(['customFieldSet', 'customFieldSet.relations']
            );

        return $this->customFieldRepository->search($criteria, $salesChannelContext->getContext());
    }
}
