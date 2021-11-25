<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Mapping;

use ReflectionException;
use ReflectionProperty;
use Shopgate\Shopware\Exceptions\MissingContextException;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use ShopgateConfig;
use ShopgateLibraryException;

class ConfigMapping extends ShopgateConfig
{
    protected array $product_types_to_export = [];
    protected ConfigBridge $configBridge;

    /**
     * @param ConfigBridge $configBridge
     * @throws ReflectionException
     */
    public function initShopwareConfig(ConfigBridge $configBridge): void
    {
        $this->configBridge = $configBridge;
        $this->setShopIsActive($this->configBridge->get('isActive'));
        $this->setCustomerNumber($this->configBridge->get('customerNumber'));
        $this->setShopNumber($this->configBridge->get('shopNumber'));
        $this->setApikey($this->configBridge->get('apiKey'));
        $this->setServer($this->configBridge->get('server', 'live'));
        $this->setProductTypesToExport($this->configBridge->get('productTypesToExport'));
        if ($this->configBridge->get('apiUrl', false)) {
            $this->setApiUrl($this->configBridge->get('apiUrl'));
        }
    }

    /**
     * Writes the given fields to magento
     *
     * @param array $fieldList
     * @param boolean $validate
     *
     * @throws ShopgateLibraryException
     * @throws MissingContextException
     * @throws ReflectionException
     */
    public function save(array $fieldList, $validate = true): void
    {
        if ($validate) {
            $this->validate($fieldList);
        }
        foreach ($fieldList as $key) {
            if (!property_exists($this, $key)) {
                continue;
            }
            $value = $this->castToType($this->{$key}, $key);
            $key = ['shop_is_active' => 'is_active'][$key] ?? $key;
            $this->configBridge->set($this->camelize($key), $value);
        }
    }

    /**
     * @param string[]|string $value
     * @throws ReflectionException
     */
    public function setProductTypesToExport($value): void
    {
        $this->product_types_to_export = $this->castToType($value, 'product_types_to_export');
    }

    /**
     * @return string[]
     */
    public function getProductTypesToExport(): array
    {
        return $this->product_types_to_export;
    }

    /**
     * @return bool
     */
    protected function startup(): bool
    {
        $this->setPluginName('Shopgate Go Plugin for Shopware 6');
        return parent::startup();
    }

    /**
     * Cast a given property value to the matching property type
     *
     * @param mixed $value
     * @param string $property
     *
     * @return array|boolean|number|string|integer
     * @throws ReflectionException
     */
    private function castToType($value, string $property)
    {
        $type = $this->getPropertyType($property);

        switch ($type) {
            case 'string[]':
            case 'array':
                return is_array($value) ? $value : array_map('trim', explode(',', $value));
            case 'bool':
            case 'boolean':
                return (boolean)$value;
            case 'int':
            case 'integer':
                return (int)$value;
            case 'string':
                return (string)$value;
            default:
                return $value;
        }
    }

    /**
     * Fetches the property type
     *
     * @param string $property
     *
     * @return string
     * @throws ReflectionException
     */
    private function getPropertyType(string $property): string
    {
        if (!array_key_exists($property, get_class_vars(__CLASS__))) {
            return 'string';
        }

        $r = new ReflectionProperty(__CLASS__, $property);
        if ($r->getType() && $r->getType()->getName()) {
            return $r->getType()->getName();
        }
        $doc = $r->getDocComment();
        /** @noinspection PhpExpressionResultUnusedInspection */
        /** @noinspection RegExpRedundantEscape */
        $doc ? preg_match_all('#@var ([a-zA-Z-_]*(\[\])?)(.*?)\n#s', $doc, $annotations) : null;
        $value = 'string';
        if (isset($annotations[1][0])) {
            $value = $annotations[1][0];
        }

        return $value;
    }
}
