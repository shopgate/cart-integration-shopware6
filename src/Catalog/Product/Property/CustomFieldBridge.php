<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Property;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\CustomFieldCollection;

class CustomFieldBridge
{
    private EntityRepository $customFieldRepository;
    private ContextManager $contextManager;

    public function __construct(EntityRepository $customFieldRepository, ContextManager $contextManager)
    {
        $this->customFieldRepository = $customFieldRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @return CustomFieldCollection|EntityCollection
     */
    public function getAllProductFieldSets(): CustomFieldCollection
    {
        $criteria = (new Criteria())
            ->addAssociation('customFieldSet')
            ->addAssociation('customFieldSet.relations')
            ->addFilter(new EqualsFilter('active', 1))
            ->addFilter(new EqualsFilter('customFieldSet.active', 1))
            ->addFilter(new EqualsFilter('customFieldSet.relations.entityName', 'product'));
        $criteria->setTitle('shopgate::custom-field::all');
        return $this->customFieldRepository->search(
            $criteria, $this->contextManager->getSalesContext()->getContext()
        )->getEntities();
    }
}
