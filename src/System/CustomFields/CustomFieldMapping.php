<?php
declare(strict_types=1);

namespace Shopgate\Shopware\System\CustomFields;

use ShopgateAddress;
use ShopgateCustomer;
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
     * @param Entity $detailedCustomer
     * @return ShopgateOrderCustomField[]
     */
    public function mapToShopgateCustomFields(Entity $detailedCustomer): array
    {
        $customFields = [];
        foreach ($this->whitelist as $method => $type) {
            if ($detailedCustomer->has($method) && ($value = $detailedCustomer->get($method))) {
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
     * @param ShopgateCustomer|ShopgateAddress $entity
     * @return array<string, string|array>
     */
    public function mapToShopwareCustomFields($entity): array
    {
        $data = [];
        foreach ($entity->getCustomFields() as $customField) {
            $type = $this->whitelist[$customField->getInternalFieldName()] ?? null;
            $value = $customField->getValue();
            if ($type && !empty($value)) {
                $data[$customField->getInternalFieldName()] = $type === 'array' ? [$value] : $customField->getValue();
            }
        }

        return $data;
    }
}
