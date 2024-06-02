<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Catalog;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(CategoryProductEntity $entity)
 * @method void set(string $key, CategoryProductEntity $entity)
 * @method CategoryProductEntity[] getIterator()
 * @method CategoryProductEntity[] getElements()
 * @method CategoryProductEntity|null get(string $key)
 * @method CategoryProductEntity|null first()
 * @method CategoryProductEntity|null last()
 * @method CategoryProductCollection filterByProperty(string $property, $value)
 */
class CategoryProductCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CategoryProductEntity::class;
    }
}
