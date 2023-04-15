<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Order;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
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
                (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
                (new FkField('sw_order_id', 'shopwareOrderId', OrderDefinition::class))->addFlags(new Required(),
                    new ApiAware()),
                (new ReferenceVersionField(OrderDefinition::class, 'sw_order_version_id'))->addFlags(new Required()),
                (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class)),
                (new NumberRangeField('shopgate_order_number', 'shopgateOrderNumber'))->addFlags(new ApiAware()),
                (new BoolField('is_sent', 'isSent'))->addFlags(new ApiAware()),
                (new BoolField('is_cancellation_sent', 'isCancelled'))->addFlags(new ApiAware()),
                (new BoolField('is_paid', 'isPaid'))->addFlags(new ApiAware()),
                (new BoolField('is_test', 'isTest'))->addFlags(new ApiAware()),
                (new ObjectField('received_data', 'receivedData'))->addFlags(new ApiAware()),
                (new OneToOneAssociationField('order', 'sw_order_id', 'id', OrderDefinition::class,
                    false))->addFlags(new ApiAware()),
                (new ManyToOneAssociationField(
                    'salesChannel',
                    'sales_channel_id',
                    SalesChannelDefinition::class,
                    'id',
                    false
                ))->addFlags(new ApiAware())
            ] + $this->defaultFields());
    }
}
