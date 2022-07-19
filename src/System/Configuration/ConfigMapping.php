<?php

declare(strict_types=1);

namespace Shopgate\Shopware\System\Configuration;

use ReflectionException;
use ReflectionProperty;
use ShopgateConfig;
use ShopgateLibraryException;
use Symfony\Component\Filesystem\Filesystem;

class ConfigMapping extends ShopgateConfig
{
    protected array $product_types_to_export = [];
    protected ConfigBridge $configReader;

    public function setConfigBridge(ConfigBridge $configBridge): ConfigMapping
    {
        $this->configReader = $configBridge;

        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function initShopwareConfig(array $data = []): void
    {
        $this->loadArray($data);
        $this->setLogFolderPath(implode('/', [$this->getLogFolderPath(), $this->getShopNumber()]));
        $this->setCacheFolderPath(implode('/', [$this->getCacheFolderPath(), $this->getShopNumber()]));
        $this->setExportFolderPath(implode('/', [$this->getExportFolderPath(), $this->getShopNumber()]));
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

    public function initFolderStructure(Filesystem $filesystem): void
    {
        array_map(static function (string $path) use ($filesystem) {
            if (!$filesystem->exists($path)) {
                $filesystem->mkdir($path);
            }
        }, [$this->getLogFolderPath(), $this->getCacheFolderPath(), $this->getExportFolderPath()]);
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

    /**
     * Writes the given fields
     *
     * @param array $fieldList
     * @param boolean $validate
     *
     * @throws ReflectionException
     * @throws ShopgateLibraryException
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
            $this->configReader->set($this->camelize($key), $value);
        }
    }
}
