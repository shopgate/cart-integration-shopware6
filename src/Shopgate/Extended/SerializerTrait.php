<?php
declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate_Helper_DataStructure;

trait SerializerTrait
{
    protected array $decodedInfo = [];
    protected ?Shopgate_Helper_DataStructure $jsonHelper = null;

    /**
     * Retrieves existing internal info & applies to cart
     */
    public function initializeTrait(): void
    {
        $this->jsonHelper = new Shopgate_Helper_DataStructure();
        $result = $this->jsonHelper->jsonDecode($this->getUtilityInternalInfo(), true) ?? [];
        if (count($result)) {
            $this->setDecodedInfo($result);
        }
    }

    /**
     * Rewritten method to retrieve the right internal info field
     */
    public function getUtilityInternalInfo(): ?string
    {
        $result = $this->findInternalInfo();

        return array_pop($result);
    }

    public function addDecodedInfo(array $info): self
    {
        if (null === $this->jsonHelper) {
            $this->initializeTrait();
        }
        $this->decodedInfo = array_merge($this->decodedInfo, $info);

        return $this;
    }

    public function getDecodedInfo(): array
    {
        if (null === $this->jsonHelper) {
            $this->initializeTrait();
        }
        return $this->decodedInfo;
    }

    public function setDecodedInfo(array $decodedInfo): self
    {
        if (null === $this->jsonHelper) {
            $this->initializeTrait();
        }
        $this->decodedInfo = $decodedInfo;

        return $this;
    }

    public function toArray(): array
    {
        if (null === $this->jsonHelper) {
            $this->initializeTrait();
        }
        $internalInfo = $this->jsonHelper->jsonDecode($this->getUtilityInternalInfo(), true);
        $encode = array_merge($this->decodedInfo, is_array($internalInfo) ? $internalInfo : []);
        $encoded = $this->jsonHelper->jsonEncode($encode);
        $this->setUtilityInternalInfo($encoded);

        return parent::toArray();
    }

    public function setUtilityInternalInfo(string $data): self
    {
        $result = $this->findInternalInfo();
        if ($key = key($result)) {
            $this->{$key} = $data;
        }

        return $this;
    }

    /**
     * Get internal_*_info key/value so that we can decode
     */
    private function findInternalInfo(): ?array
    {
        return array_filter(parent::toArray(), static function ($key) {
            return strpos($key, 'internal') !== false && strpos($key, 'info') !== false;
        }, ARRAY_FILTER_USE_KEY);
    }
}
