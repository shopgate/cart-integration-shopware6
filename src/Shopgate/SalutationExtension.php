<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate;

use Shopgate\Shopware\Shopgate\Salutations\ShopgateSalutationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Salutation\SalutationDefinition;

class SalutationExtension extends EntityExtension
{
    public const PROPERTY = 'shopgateSalutation';

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToOneAssociationField(self::PROPERTY, 'id', 'sw_salutation_id', ShopgateSalutationDefinition::class, true)
        );
    }

    public function getEntityName(): string
    {
        return SalutationDefinition::ENTITY_NAME;
    }
}
