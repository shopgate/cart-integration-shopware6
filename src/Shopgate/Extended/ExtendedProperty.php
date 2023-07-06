<?php declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate\Shopware\Catalog\Mapping\PriceMapping;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_Catalog_Property;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;

class ExtendedProperty extends Shopgate_Model_Catalog_Property
{

    public function __construct(private readonly PriceMapping $priceMapping, private readonly Formatter $formatter)
    {
    }

    /**
     * @param bool|string|Price $value
     */
    public function setValue($value): self
    {
        if ($value instanceof Price) {
            $value = (string)$this->priceMapping->mapPrice($value);
        } elseif ($value === false) {
            $value = '0';
        }
        parent::setValue((string)$value);

        return $this;
    }

    public function setLabel(string $value): self
    {
        parent::setLabel($value);

        return $this;
    }

    /**
     * Helps with translating the label
     */
    public function setAndTranslateLabel(string $key, array $properties = [], ?string $domain = 'storefront'): self
    {
        parent::setLabel($this->formatter->translate($key, $properties, $domain));

        return $this;
    }

    public function setUid(string $value): self
    {
        /** @noinspection PhpStrictTypeCheckingInspection */
        parent::setUid($value);

        return $this;
    }
}
