<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Mapping\Events;

use Shopgate\Shopware\Shopgate\Extended\ExtendedProperty;
use Shopgate_Model_Catalog_Property;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;

class AfterSimpleProductPropertyMapEvent
{
    private array $properties;
    private SalesChannelProductEntity $item;

    /**
     * @param ExtendedProperty[]|Shopgate_Model_Catalog_Property[] $properties
     * @param SalesChannelProductEntity $item
     */
    public function __construct(
        array $properties,
        SalesChannelProductEntity $item
    ) {
        $this->properties = $properties;
        $this->item = $item;
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
