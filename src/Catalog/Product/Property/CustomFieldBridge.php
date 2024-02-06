<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Property;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\CustomFieldCollection;

readonly class CustomFieldBridge
{

    public function __construct(private EntityRepository $customFieldRepository, private ContextManager $contextManager)
    {
    }

    public function getAllProductFieldSets(): CustomFieldCollection|EntityCollection
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
