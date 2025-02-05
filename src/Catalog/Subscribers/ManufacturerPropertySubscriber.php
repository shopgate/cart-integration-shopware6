<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Subscribers;

use Shopgate\Shopware\Catalog\Mapping\Events\AfterSimpleProductPropertyMapEvent;
use Shopgate\Shopware\Catalog\Product\Events\BeforeProductLoadEvent;
use Shopgate\Shopware\Shopgate\Extended\ExtendedProperty;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopgate\Shopware\System\Formatter;
use Shopgate_Model_Catalog_Property;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ManufacturerPropertySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ExtendedClassFactory $classFactory,
        private readonly Formatter $formatter,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeProductLoadEvent::class => 'addManufacturerMediaCriteria',
            AfterSimpleProductPropertyMapEvent::class => 'addManufacturersAsProperties'
        ];
    }

    public function addManufacturersAsProperties(AfterSimpleProductPropertyMapEvent $event): void
    {
        $manufacturer = $event->getItem()->getManufacturer();
        if (!$manufacturer || !$this->systemConfigService->getFloat(ConfigBridge::PRODUCT_PROPERTY_EXPORT_MANUFACTURER)) {
            return;
        }

        $merged = array_merge($event->getProperties(), $this->createManufacturerProperty($manufacturer));
        $event->setProperties($merged);
    }

    public function addManufacturerMediaCriteria(BeforeProductLoadEvent $event): void
    {
        if (!$this->systemConfigService->getFloat(ConfigBridge::PRODUCT_PROPERTY_EXPORT_MANUFACTURER)) {
            return;
        }

        $event->getCriteria()->addAssociations([
            'manufacturer.media',
            'children.manufacturer.media'
        ]);
    }

    /**
     * @return ExtendedProperty[]|Shopgate_Model_Catalog_Property[]
     */
    private function createManufacturerProperty(ProductManufacturerEntity $manufacturer): array
    {
        $description = $manufacturer->getTranslation('description');
        $media = $manufacturer->getMedia();
        $list = [
            'name' => $manufacturer->getTranslation('name'),
            ...$description ? ['description' => $description] : [],
            ...$media ? ['mediaUrl' => $media->getUrl()] : []
        ];

        return array_map(function (string $key, string $value) {
            return $this->classFactory->createProperty()
                ->setUid('manufacturer_' . $key)
                ->setLabel('Manufacturer ' . $this->formatter->camelCaseToSpaced($key))
                ->setValue($value);
        }, array_keys($list), array_values($list));
    }
}
