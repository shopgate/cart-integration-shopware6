<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Tax;

use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity;
use Shopware\Core\System\Tax\TaxEntity;

class TaxBridge
{
    private EntityRepositoryInterface $taxRepository;
    private EntityRepositoryInterface $taxRuleRepository;
    private EntityRepositoryInterface $taxRuleTypeRepository;
    private ContextManager $contextManager;

    public function __construct(
        EntityRepositoryInterface $taxRepository,
        EntityRepositoryInterface $taxRuleRepository,
        EntityRepositoryInterface $taxRuleTypeRepository,
        ContextManager $contextManager
    ) {
        $this->taxRepository = $taxRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->taxRuleTypeRepository = $taxRuleTypeRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @return TaxEntity[]
     */
    public function getTaxClasses(): array
    {
        $criteria = new Criteria();
        $criteria->setTitle('shopgate::tax');
        return $this->taxRepository->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }

    /**
     * @param string $id
     * @param string $type
     * @return TaxRuleEntity[]
     */
    public function getTaxRulesByTaxId(string $id, string $type): array
    {
        $typeId = $this->getTaxRuleTypeIdByTechnicalName($type);
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('taxId', $id))
            ->addFilter(new EqualsFilter('taxRuleTypeId', $typeId));
        $criteria->setTitle('shopgate::tax-rule::tax-id-and-type');
        return $this->taxRuleRepository->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }

    public function getTaxRuleTypeIdByTechnicalName(string $technicalName): string
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->setTitle('shopgate::tax-rule::technical-name');
        $result = $this->taxRuleTypeRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getId();
    }
}
