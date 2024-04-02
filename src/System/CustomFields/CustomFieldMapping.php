<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\CustomFields;

use ShopgateAddress;
use ShopgateCustomer;
use ShopgateOrder;
use ShopgateOrderCustomField;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class CustomFieldMapping
{

    /**
     * @param array $whitelist - allowed exported custom fields
     * @param array $transformList - map of Shopgate key to Shopware key
     */
    public function __construct(private readonly array $whitelist, private readonly array $transformList = [])
    {
    }

    /**
     * @return ShopgateOrderCustomField[]
     */
    public function mapToShopgateCustomFields(Entity $entity): array
    {
        $customFields = [];
        foreach ($this->whitelist as $method => $type) {
            $mappedMethod = $this->transformList[$method] ?? $method;
            if ($entity->has($mappedMethod) && ($value = $entity->get($mappedMethod))) {
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
    public function mapToShopwareCustomFields(ShopgateCustomer|ShopgateAddress|ShopgateOrder $entity): array
    {
        $data = [];
        foreach ($entity->getCustomFields() as $customField) {
            $fieldName = $customField->getInternalFieldName();
            $value = $customField->getValue();

            $type = $this->whitelist[$fieldName] ?? null;
            $key = $this->transformList[$fieldName] ?? $fieldName;
            $isEmpty = $value === '' || $value === null;
            if ($type && !$isEmpty) {
                $data[$key] = $type === 'array' ? [$value] : $value;
            } elseif (!$isEmpty) {
                $data['customFields'][$key] = $value;
            }
        }

        return $data;
    }
}
