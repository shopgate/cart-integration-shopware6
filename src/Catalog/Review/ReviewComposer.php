<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Review;

use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;

class ReviewComposer
{

    public function __construct(private readonly ReviewBridge $reviewBridge, private readonly ReviewMapping $reviewMapping)
    {
    }

    /**
     * @return ReviewMapping[]
     */
    public function getReviews(?int $limit, ?int $offset, array $uids): array
    {
        $allReviews = $this->reviewBridge->getReviews($limit, $offset, $uids);
        if (!empty($uids)) {
            $allReviews->sortByIdArray($uids);
        }

        return $allReviews->map(function (ProductReviewEntity $reviewEntity) {
            $reviewModel = clone $this->reviewMapping;
            /** @noinspection PhpParamsInspection */
            $reviewModel->setItem($reviewEntity);

            return $reviewModel->generateData();
        });
    }
}
