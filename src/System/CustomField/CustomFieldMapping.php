<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\CustomField;

use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\System\Formatter;
use ShopgateOrderCustomField;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\System\CustomField\CustomFieldEntity;

class CustomFieldMapping
{
    private Formatter $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param Entity $entity - an entity that uses the EntityCustomFieldsTrait
     * @param EntityCollection $shopwareCustomFields
     * @return ShopgateOrderCustomField[]
     * @throws MissingContextException
     */
    public function toShopgate(Entity $entity, EntityCollection $shopwareCustomFields): array
    {
        $list = [];
        if (!method_exists($entity, 'getCustomFields')) {
            return $list;
        }

        $locale = $this->formatter->getLocaleCode();
        foreach ($entity->getCustomFields() as $key => $value) {
            $sgCustomField = new ShopgateOrderCustomField();
            $sgCustomField->setInternalFieldName($key);
            $sgCustomField->setValue($value);
            if ($shopwareCustomFields->count() > 0) {
                /** @var CustomFieldEntity|null $customField */
                $customField = $shopwareCustomFields->filterByProperty('name', $key)->first();
                $cfg = $customField ? $customField->getConfig() : ['label' => []];
                $sgCustomField->setLabel($cfg['label'][$locale] ?? array_shift($cfg['label']));
            }
            $sgCustomField->getLabel() ?: $sgCustomField->setLabel($key);
            $list[] = $sgCustomField;
        }

        return $list;
    }
}
