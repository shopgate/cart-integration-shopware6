<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\CustomField;

use Shopgate\Shopware\Exceptions\MissingContextException;
use ShopgateOrderCustomField;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomFieldComposer
{
    private CustomFieldBridge $customFieldBridge;
    private CustomFieldMapping $customFieldMapping;

    public function __construct(CustomFieldBridge $customFieldBridge, CustomFieldMapping $customFieldMapping)
    {
        $this->customFieldBridge = $customFieldBridge;
        $this->customFieldMapping = $customFieldMapping;
    }

    /**
     * @param string $type
     * @param Entity $entity
     * @param SalesChannelContext $salesChannelContext
     * @return ShopgateOrderCustomField[]
     * @throws MissingContextException
     */
    public function toShopgate(string $type, Entity $entity, SalesChannelContext $salesChannelContext): array
    {
        $customFields = $this->customFieldBridge->getFieldList($type, $salesChannelContext);

        return $this->customFieldMapping->toShopgate($entity, $customFields);
    }
}
