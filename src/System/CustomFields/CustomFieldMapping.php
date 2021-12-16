<?php
declare(strict_types=1);

namespace Shopgate\Shopware\System\CustomFields;

use ShopgateAddress;
use ShopgateCustomer;
use ShopgateOrder;
use ShopgateOrderCustomField;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class CustomFieldMapping
{
    private array $whitelist;

    public function __construct(array $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * @param Entity $entity
     * @return ShopgateOrderCustomField[]
     */
    public function mapToShopgateCustomFields(Entity $entity): array
    {
        $customFields = [];
        foreach ($this->whitelist as $method => $type) {
            if ($entity->has($method) && ($value = $entity->get($method))) {
                $customField = new ShopgateOrderCustomField();
                $customField->setLabel($method);
                $customField->setInternalFieldName($method);
                $customField->setValue($type === 'array' ? reset($value) : $value);
                $customFields[] = $customField;
            }
        }

        return $customFields;
    }

    /**
     * Note that whitelist does not filter incoming data, just
     * used as reference to `type` of field
     *
     * @param ShopgateCustomer|ShopgateAddress|ShopgateOrder $entity
     * @return array<string, string|array>
     */
    public function mapToShopwareCustomFields($entity): array
    {
        $data = [];
        foreach ($entity->getCustomFields() as $customField) {
            $type = $this->whitelist[$customField->getInternalFieldName()] ?? null;
            $value = $customField->getValue();
            $isEmpty = $value === '' || $value === null;
            if ($type && !$isEmpty) {
                $data[$customField->getInternalFieldName()] = $type === 'array' ? [$value] : $value;
            } elseif (!$isEmpty) {
                $data['customFields'][$customField->getInternalFieldName()] = $value;
            }
        }

        return $data;
    }
}
