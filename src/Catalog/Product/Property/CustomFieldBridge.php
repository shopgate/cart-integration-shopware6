<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Property;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\CustomFieldCollection;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomFieldBridge
{

    public function __construct(
        private readonly EntityRepository $customFieldRepository,
        private readonly ContextManager $contextManager,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    /**
     * @return CustomFieldCollection|EntityCollection
     */
    public function getAllProductFieldSets(): CustomFieldCollection
    {
        $setRelationshipTypes = $this->systemConfigService->get(ConfigBridge::SYSTEM_CONFIG_PROD_PROP_DOMAIN);
        if (empty($setRelationshipTypes)) {
            return new CustomFieldCollection();
        }

        $criteria = (new Criteria())
            ->addAssociation('customFieldSet')
            ->addAssociation('customFieldSet.relations')
            ->addFilter(new EqualsFilter('active', 1))
            ->addFilter(new EqualsFilter('customFieldSet.active', 1))
            ->addFilter(
                new EqualsAnyFilter(
                    'customFieldSet.relations.entityName',
                    array_map(fn(array $config) => $config['value'], $setRelationshipTypes)
                )
            );
        $criteria->setTitle('shopgate::custom-field::all');
        return $this->customFieldRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->getEntities();
    }
}
