<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Review;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;

class ReviewComposer
{
    private ReviewMapping $reviewMapping;
    private ReviewBridge $reviewBridge;

    public function __construct(ReviewBridge $reviewBridge, ReviewMapping $reviewMapping)
    {
        $this->reviewBridge = $reviewBridge;
        $this->reviewMapping = $reviewMapping;
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param array $uids
     * @return ReviewMapping[]
     * @throws MissingContextException
     */
    public function getReviews(?int $limit, ?int $offset, array $uids): array
    {
        $allReviews = $this->reviewBridge->getReviews($limit, $offset, $uids);
        if (!empty($uids)) {
            $allReviews->sortByIdArray($uids);
        }

        return $allReviews->map(function (ProductReviewEntity $reviewEntity) {
            $reviewModel = clone $this->reviewMapping;
            $reviewModel->setItem($reviewEntity);

            return $reviewModel->generateData();
        });
    }
}
