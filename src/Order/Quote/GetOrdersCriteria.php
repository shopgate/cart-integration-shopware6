<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Quote;

use Shopgate\Shopware\Shopgate\NativeOrderExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class GetOrdersCriteria extends Criteria
{

    public function setShopgateSort(string $sortOrder): self
    {
        $this->addSorting(new FieldSorting(
            $this->mapSortField($sortOrder),
            $this->mapSortDirection($sortOrder)
        ));

        return $this;
    }

    public function addShopgateAssociations(): self
    {
        $this->addAssociations([
            'currency',
            'deliveries',
            'stateMachineState',
            'lineItems',
            'lineItems.product',
            NativeOrderExtension::PROPERTY
        ]);

        return $this;
    }

    public function setShopgateFromDate(string $date): self
    {
        if (!empty($date) && ($timestamp = strtotime($date))) {
            $this->addFilter(new RangeFilter('createdAt', [
                RangeFilter::GTE => date('Y-m-d H:i:s', $timestamp)
            ]));
        }

        return $this;
    }

    private function mapSortField(string $shopgateSort): string
    {
        [$sortField] = explode('_', $shopgateSort);

        return $sortField === 'created' ? 'createdAt' : 'updatedAt';
    }

    private function mapSortDirection(string $shopgateSort): string
    {
        [, $sortDirection] = explode('_', $shopgateSort);

        return $sortDirection === 'desc' ? FieldSorting::DESCENDING : FieldSorting::ASCENDING;
    }
}
