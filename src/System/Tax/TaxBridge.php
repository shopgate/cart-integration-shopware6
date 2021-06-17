<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Tax;

use Shopgate\Shopware\Exceptions\MissingContextException;
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

    /**
     * @param EntityRepositoryInterface $taxRepository
     * @param EntityRepositoryInterface $taxRuleRepository
     * @param EntityRepositoryInterface $taxRuleTypeRepository
     * @param ContextManager $contextManager
     */
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
     * @throws MissingContextException
     */
    public function getTaxClasses(): array
    {
        return $this->taxRepository->search(new Criteria(), $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }

    /**
     * @param string $id
     * @param string $type
     * @return TaxRuleEntity[]
     * @throws MissingContextException
     */
    public function getTaxRulesByTaxId(string $id, string $type): array
    {
        $typeId = $this->getTaxRuleTypeIdByTechnicalName($type);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxId', $id));
        $criteria->addFilter(new EqualsFilter('taxRuleTypeId', $typeId));

        return $this->taxRuleRepository->search($criteria, $this->contextManager->getSalesContext()->getContext())
            ->getEntities()
            ->getElements();
    }

    /**
     * @param string $technicalName
     * @return string
     * @throws MissingContextException
     */
    public function getTaxRuleTypeIdByTechnicalName(string $technicalName): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $result = $this->taxRuleTypeRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        )->first();

        return $result->getId();
    }
}
