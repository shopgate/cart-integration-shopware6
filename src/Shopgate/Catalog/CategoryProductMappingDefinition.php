<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Catalog;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class CategoryProductMappingDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'shopgate_go_category_product_mapping';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return CategoryProductCollection::class;
    }

    public function getEntityClass(): string
    {
        return CategoryProductEntity::class;
    }

    public function since(): ?string
    {
        return '6.6.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('category_id', 'categoryId', CategoryDefinition::class))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new IntField('sort_order', 'sortOrder'))->addFlags(new Required(), new ApiAware()),
            (new FkField('language_id', 'languageId', LanguageDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new ReferenceVersionField(CategoryDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false),
            new ManyToOneAssociationField('category', 'category_id', CategoryDefinition::class, 'id', false),
            new ManyToOneAssociationField('language', 'language_id', LanguageDefinition::class, 'id', false)
        ]);
    }
}
