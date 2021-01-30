<?php

namespace Shopgate\Shopware\Catalog\Product\Property;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class PropertyBridge
{
    /** @var EntityRepositoryInterface */
    private $propertyGroupOptionRepo;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param EntityRepositoryInterface $propertyGroupOptionRepo
     * @param ContextManager $contextManager
     */
    public function __construct(EntityRepositoryInterface $propertyGroupOptionRepo, ContextManager $contextManager)
    {
        $this->propertyGroupOptionRepo = $propertyGroupOptionRepo;
        $this->contextManager = $contextManager;
    }

    /**
     * @param string[] $uids
     * @return PropertyGroupCollection|null
     * @throws MissingContextException
     */
    public function getGroupOptions(array $uids = []): ?PropertyGroupCollection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->propertyGroupOptionRepo->search(
            new Criteria($uids),
            $this->contextManager->getSalesContext()->getContext()
        )->getEntities();
    }
}
