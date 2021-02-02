<?php

namespace Shopgate\Shopware\Catalog\Product\Price;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

class PriceRuleBridge
{
    /** @var EntityRepositoryInterface */
    private $entityRepository;
    /** @var ContextManager */
    private $contextManager;

    /**
     * @param EntityRepositoryInterface $entityRepository
     * @param ContextManager $contextManager
     */
    public function __construct(EntityRepositoryInterface $entityRepository, ContextManager $contextManager)
    {
        $this->entityRepository = $entityRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @return EntitySearchResult
     * @throws MissingContextException
     */
    public function getAllRules(): EntitySearchResult
    {
        $criteria = (new Criteria())->addAssociation('ruleCondition');
        return $this->entityRepository->search($criteria, $this->contextManager->getSalesContext()->getContext());
    }
}
