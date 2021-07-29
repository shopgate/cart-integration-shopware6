<?php
declare(strict_types=1);

namespace Shopgate\Shopware\Shopgate\Extended;

use Shopgate_Helper_DataStructure;

trait SerializerTrait
{
    protected array $decodedInfo = [];
    protected Shopgate_Helper_DataStructure $jsonHelper;

    /**
     * @param array $data The data the container should be initialized with.
     */
    public function __construct($data = array())
    {
        parent::__construct($data);
        $this->jsonHelper = new Shopgate_Helper_DataStructure();

        $result = $this->jsonHelper->jsonDecode($this->getUtilityInternalInfo(), true) ?? [];
        if (count($result)) {
            $this->setDecodedInfo($result);
        }
    }

    /**
     * Rewritten method to retrieve the right internal info field
     *
     * @return string|null
     */
    public function getUtilityInternalInfo(): ?string
    {
        // get internal_*_info key/value so that we can decode
        $result = array_filter(parent::toArray(), static function ($key) {
            return strpos($key, 'internal') !== false && strpos($key, 'info') !== false;
        }, ARRAY_FILTER_USE_KEY);

        return array_pop($result);
    }

    /**
     * @param array $info
     * @return self
     */
    public function addDecodedInfo(array $info): self
    {
        $this->decodedInfo = array_merge($this->decodedInfo, $info);

        return $this;
    }

    /**
     * @return array
     */
    public function getDecodedInfo(): array
    {
        return $this->decodedInfo;
    }

    /**
     * @param array $decodedInfo
     * @return self
     */
    public function setDecodedInfo(array $decodedInfo): self
    {
        $this->decodedInfo = $decodedInfo;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $internalInfo = $this->jsonHelper->jsonDecode($this->getUtilityInternalInfo(), true);
        $encode = array_merge($this->decodedInfo, is_array($internalInfo) ? $internalInfo : []);
        $encoded = $this->jsonHelper->jsonEncode($encode);
        $this->setUtilityInternalInfo($encoded);

        return parent::toArray();
    }

    /**
     * @param string $data
     * @return self
     */
    public function setUtilityInternalInfo(string $data): self
    {
        // get internal_*_info key/value so that we can decode
        $result = array_filter(parent::toArray(), static function ($key) {
            return strpos($key, 'internal') !== false && strpos($key, 'info') !== false;
        }, ARRAY_FILTER_USE_KEY);

        if ($key = key($result)) {
            $this->{$key} = $data;
        }

        return $this;
    }
}
