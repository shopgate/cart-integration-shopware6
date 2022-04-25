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
    private array $transformList;

    public function __construct(array $whitelist, array $transformList = [])
    {
        $this->whitelist = $whitelist;
        $this->transformList = $transformList;
    }

    /**
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
            $key = $this->transform($customField->getInternalFieldName());
            $isEmpty = $value === '' || $value === null;
            if ($type && !$isEmpty) {
                $data[$key] = $type === 'array' ? [$value] : $value;
            } elseif (!$isEmpty) {
                $data['customFields'][$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Helps with translating incoming Shopgate keys to Shopware
     */
    private function transform(string $key)
    {
        return $this->transformList[$key] ?? $key;
    }
}
