<?php

namespace Shopgate\Shopware\Export;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Throwable;

class ConfigExport
{
    /** @var string */
    private $shopwareVersion;
    /** @var EntityRepositoryInterface */
    private $pluginRepository;

    /**
     * @param EntityRepositoryInterface $pluginRepository
     * @param string $shopwareVersion
     */
    public function __construct(
        EntityRepositoryInterface $pluginRepository,
        string $shopwareVersion
    ) {
        $this->pluginRepository = $pluginRepository;
        $this->shopwareVersion = $shopwareVersion;
    }

    /**
     * @return string
     */
    public function getShopwareVersion(): string
    {
        return $this->shopwareVersion;
    }

    /**
     * @param Context $context
     * @return string
     */
    public function getShopgatePluginVersion(Context $context): string
    {
        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', 'ShopgateModule'));
            $result = $this->pluginRepository->search($criteria, $context)->first();
            $version = $result->getVersion();
        } catch (Throwable $e) {
            $version = 'not installed';
        }
        return $version;
    }
}
