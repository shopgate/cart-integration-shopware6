<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Review;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ReviewBridge
{

    private ContextManager $contextManager;
    private EntityRepositoryInterface $reviewRepository;

    public function __construct(EntityRepositoryInterface $reviewRepository, ContextManager $contextManager)
    {
        $this->reviewRepository = $reviewRepository;
        $this->contextManager = $contextManager;
    }

    /**
     * @throws MissingContextException
     */
    public function getReviews(?int $limit, ?int $offset, array $uids): EntitySearchResult
    {
        $channel = $this->contextManager->getSalesContext();
        $criteria = (new Criteria($uids))
            ->setLimit($limit)
            ->setOffset($offset)
            ->addFilter(new EqualsFilter('status', true))
            ->addFilter(new EqualsFilter('salesChannelId', $channel->getSalesChannelId()))
            ->addAssociation('customer')
            ->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setTitle('shopgate::product-review::detailed');

        return $this->reviewRepository->search($criteria, $channel->getContext());
    }
}
