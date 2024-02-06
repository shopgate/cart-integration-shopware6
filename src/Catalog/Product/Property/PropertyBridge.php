<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Property;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

readonly class PropertyBridge
{

    public function __construct(private EntityRepository $propertyGroupOptionRepo, private ContextManager $contextManager)
    {
    }

    /**
     * @param string[] $uids
     */
    public function getGroupOptions(array $uids = []): null|PropertyGroupCollection|EntityCollection
    {
        $criteria = new Criteria(!empty($uids) ? $uids : null);
        $criteria->setTitle('shopgate::property-group-option::ids');
        return $this->propertyGroupOptionRepo->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->getEntities();
    }
}
