<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Review;

use Shopgate_Model_Catalog_Review;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;

class ReviewMapping extends Shopgate_Model_Catalog_Review
{
    /** @var ProductReviewEntity */
    protected $item;

    public function setUid(): void
    {
        parent::setUid($this->item->getId());
    }

    public function setItemUid(): void
    {
        parent::setItemUid($this->item->getProductId());
    }

    public function setScore(): void
    {
        parent::setScore((int)round($this->item->getPoints() * 2, 0, PHP_ROUND_HALF_UP));
    }

    public function setReviewerName(): void
    {
        parent::setReviewerName($this->item->getCustomer()
            ? $this->item->getCustomer()->getFirstName() . ' ' . $this->item->getCustomer()->getLastName()[0] . '.'
            : '*****'
        );
    }

    public function setDate(): void
    {
        if ($this->item->getCreatedAt()) {
            parent::setDate($this->item->getCreatedAt()->format('Y-m-d'));
        }
    }

    public function setTitle(): void
    {
        parent::setTitle($this->item->getTitle());
    }

    public function setText(): void
    {
        parent::setText($this->item->getContent());
    }
}
