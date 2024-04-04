<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Review;

use Shopgate\Shopware\Catalog\Review\Events\BeforeReviewLoadEvent;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ReviewBridge
{

    public function __construct(
        private readonly ContextManager $contextManager,
        private readonly EntityRepository $reviewRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getReviews(?int $limit, ?int $offset, array $uids): EntitySearchResult
    {
        $channel = $this->contextManager->getSalesContext();
        $criteria = (new Criteria(!empty($uids) ? $uids : null))
            ->setLimit($limit)
            ->setOffset($offset)
            ->addFilter(new EqualsFilter('status', true))
            ->addAssociation('customer')
            ->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setTitle('shopgate::product-review::detailed');

        $this->eventDispatcher->dispatch(new BeforeReviewLoadEvent($criteria, $channel));

        return $this->reviewRepository->search($criteria, $channel->getContext());
    }
}
