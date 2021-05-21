<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Property;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\CustomFieldCollection;

class CustomFieldBridge
{
    /** @var EntityRepositoryInterface */
    private $customFieldRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param EntityRepositoryInterface $customFieldRepository
     * @param ContextManager $contextManager
     */
    public function __construct(EntityRepositoryInterface $customFieldRepository, ContextManager $contextManager)
    {
        $this->customFieldRepository = $customFieldRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @return CustomFieldCollection|EntityCollection
     * @throws MissingContextException
     */
    public function getAllProductFieldSets(): CustomFieldCollection
    {
        return $this->customFieldRepository->search(
            (new Criteria())
                ->addAssociation('customFieldSet')
                ->addAssociation('customFieldSet.relations')
                ->addFilter(new EqualsFilter('active', 1))
                ->addFilter(new EqualsFilter('customFieldSet.active', 1))
                ->addFilter(new EqualsFilter('customFieldSet.relations.entityName', 'product'))
            ,
            $this->contextManager->getSalesContext()->getContext()
        )->getEntities();
    }
}
