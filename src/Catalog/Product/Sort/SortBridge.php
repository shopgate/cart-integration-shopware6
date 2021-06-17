<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Product\Sort;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class SortBridge
{
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $productSortingRepository;
    private ContextManager $contextManager;

    /**
     * @param EntityRepositoryInterface $productSortingRepository
     * @param SystemConfigService $systemConfigService
     * @param ContextManager $contextManager
     */
    public function __construct(
        EntityRepositoryInterface $productSortingRepository,
        SystemConfigService $systemConfigService,
        ContextManager $contextManager
    ) {
        $this->productSortingRepository = $productSortingRepository;
        $this->systemConfigService = $systemConfigService;
        $this->contextManager = $contextManager;
    }

    /**
     * @return FieldSorting[]
     * @throws MissingContextException
     */
    public function getDefaultSorting(): array
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('key', $this->getSystemDefaultSorting()));
        /** @var ProductSortingCollection $collection */
        $collection = $this->productSortingRepository->search(
            $criteria,
            $this->contextManager->getSalesContext()->getContext()
        );

        $entity = $collection->first();
        if ($entity) {
            $fields = $entity->getFields();
            $sortList = [];
            foreach ($fields as $field) {
                $sortList[] = new FieldSorting($field['field'], strtoupper($field['order']));
            }
            return $sortList;
        }
        return [new FieldSorting('product.name', FieldSorting::ASCENDING)];
    }

    /**
     * @return string
     * @throws MissingContextException
     */
    private function getSystemDefaultSorting(): string
    {
        return $this->systemConfigService->getString(
            'core.listing.defaultSorting',
            $this->contextManager->getSalesContext()->getSalesChannel()->getId()
        );
    }
}
