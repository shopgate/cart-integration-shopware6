<?php

namespace Shopgate\Shopware\Shopgate\Order;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class ShopgateOrderDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'shopgate_order';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ShopgateOrderCollection::class;
    }

    public function getEntityClass(): string
    {
        return ShopgateOrderEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
                (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
                (new FkField('sw_order_id', 'shopwareOrderId', OrderDefinition::class))->addFlags(new Required()),
                new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),
                new NumberRangeField('shopgate_order_number', 'shopgateOrderNumber'),
                new BoolField('is_sent', 'isSent'),
                new BoolField('is_cancellation_sent', 'isCancelled'),
                new BoolField('is_paid', 'isPaid'),
                new BoolField('is_test', 'isTest'),
                new ObjectField('received_data', 'receivedData'),
                new OneToOneAssociationField('order', 'sw_order_id', 'id', OrderDefinition::class, false),
                new ManyToOneAssociationField(
                    'salesChannel',
                    'sales_channel_id',
                    SalesChannelDefinition::class,
                    'id',
                    false
                )
            ] + $this->defaultFields());
    }
}
