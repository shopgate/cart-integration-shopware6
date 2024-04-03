<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedProperty;
use Shopgate_Model_Catalog_Property;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;

class AfterSimpleProductPropertyMapEvent
{
    private array $properties;

    /**
     * @param ExtendedProperty[]|Shopgate_Model_Catalog_Property[] $properties
     */
    public function __construct(
        array $properties,
        private readonly SalesChannelProductEntity $item
    ) {
        $this->properties = $properties;
    }

    /**
     * @param ExtendedProperty[]|Shopgate_Model_Catalog_Property[] $properties
     * @return self
     */
    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return ExtendedProperty[]|Shopgate_Model_Catalog_Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getItem(): SalesChannelProductEntity
    {
        return $this->item;
    }
}
