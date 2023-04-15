<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Salutations;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Salutation\SalutationDefinition;

class ShopgateSalutationDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'shopgate_go_salutations';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ShopgateSalutationCollection::class;
    }

    public function getEntityClass(): string
    {
        return ShopgateSalutationEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
                (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
                (new FkField('sw_salutation_id', 'shopwareSalutationId',
                    SalutationDefinition::class))->addFlags(new Required(), new ApiAware()),
                (new StringField('value', 'value', 1))->addFlags(new ApiAware()),
                (new OneToOneAssociationField('salutation', 'sw_salutation_id', 'id',
                    SalutationDefinition::class, false))->addFlags(new ApiAware()),
            ] + $this->defaultFields());
    }
}
