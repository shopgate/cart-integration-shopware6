<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Order\ShopgateOrderDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class NativeOrderExtension extends EntityExtension
{
    public const PROPERTY = 'shopgateOrder';

    /**
     * @param FieldCollection $collection
     */
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToOneAssociationField(self::PROPERTY, 'id', 'sw_order_id', ShopgateOrderDefinition::class, false)
        );
    }

    /**
     * @return string
     */
    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }
}
