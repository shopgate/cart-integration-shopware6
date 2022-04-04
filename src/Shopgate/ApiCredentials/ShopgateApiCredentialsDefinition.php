<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\ApiCredentials;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class ShopgateApiCredentialsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'shopgate_api_credentials';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ShopgateApiCredentialsEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
                (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
                (new BoolField('active', 'active')),
                (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))
                    ->addFlags(new Required(), new ApiAware()),
                (new FkField('language_id', 'languageId', LanguageDefinition::class))
                    ->addFlags(new Required(), new ApiAware()),
                (new IntField('customer_number', 'customerNumber'))->addFlags(new Required(), new ApiAware()),
                (new IntField('shop_number', 'shopNumber'))->addFlags(new Required(), new ApiAware()),
                (new StringField('api_key', 'apiKey'))->addFlags(new Required(), new ApiAware()),

                (new ManyToOneAssociationField(
                    'salesChannel',
                    'sales_channel_id',
                    SalesChannelDefinition::class,
                    'id',
                    false
                ))->addFlags(new ApiAware()),
                (new ManyToOneAssociationField(
                    'language',
                    'language_id',
                    LanguageDefinition::class,
                    'id',
                    false
                ))->addFlags(new ApiAware())
            ] + $this->defaultFields()
        );
    }
}
