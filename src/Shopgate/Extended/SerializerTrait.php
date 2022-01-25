<?php
declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate_Helper_DataStructure;

/**
 * Takes the internal_*_info property of an object and
 * json decodes it to a different property. Also helps
 * add data seamlessly to the internal_info field.
 */
trait SerializerTrait
{
    protected array $decodedInfo = [];
    protected ?Shopgate_Helper_DataStructure $jsonHelper = null;

    /**
     * Retrieves existing internal info & applies to cart
     */
    public function initializeTrait(): void
    {
        if (null !== $this->jsonHelper) {
            return;
        }
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
        $this->initializeTrait();
        $this->decodedInfo = array_merge($this->decodedInfo, $info);

        return $this;
    }

    public function getDecodedInfo(): array
    {
        $this->initializeTrait();
        return $this->decodedInfo;
    }

    public function setDecodedInfo(array $decodedInfo): self
    {
        $this->initializeTrait();
        $this->decodedInfo = $decodedInfo;

        return $this;
    }

    /**
     * Since constructor was rewritten, it's the only way to
     * import array data
     */
    public function loadArray(array $data = []): array
    {
        $unmapped = parent::loadArray($data);
        // we want to decode only when actual data is coming in
        if (!empty($data)) {
            $this->initializeTrait();
        }

        return $unmapped;
    }

    public function toArray(): array
    {
        $this->mergeInternalInfos();

        return parent::toArray();
    }

    /**
     * Merges decodedInfo array with data in internal_*_info,
     * afterwards json encodes internal_*_info for export
     */
    public function mergeInternalInfos(): void
    {
        $this->initializeTrait();
        $internalInfo = $this->jsonHelper->jsonDecode($this->getUtilityInternalInfo(), true);
        $encode = array_merge($this->decodedInfo, is_array($internalInfo) ? $internalInfo : []);
        $encoded = $this->jsonHelper->jsonEncode($encode);
        $this->setUtilityInternalInfo($encoded);
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
