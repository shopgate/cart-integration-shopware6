<?php declare(strict_types=1);

namespace Shopgate\Shopware\Catalog\Subscribers;

use Shopgate\Shopware\Catalog\Mapping\Events\AfterSimpleProductPropertyMapEvent;
use Shopgate\Shopware\Shopgate\ExtendedClassFactory;
use Shopgate\Shopware\Storefront\ContextManager;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class CrossSellPropertySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ExtendedClassFactory $classFactory,
        private readonly AbstractProductCrossSellingRoute $crossSellingRoute,
        private readonly ContextManager $contextManager,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [AfterSimpleProductPropertyMapEvent::class => ['addCrossSellProductsAsProperties', 30]];
    }

    public function addCrossSellProductsAsProperties(AfterSimpleProductPropertyMapEvent $event): void
    {
        if (!$event->getItem()->getCrossSellings()) {
            return;
        }

        $exportType = $this->systemConfigService->getString(ConfigBridge::PRODUCT_CROSS_SELL_EXPORT);
        if ($exportType !== ConfigBridge::CROSS_SELL_EXPORT_TYPE_PROPS) {
            return;
        }

        $criteria = new Criteria();
        $criteria->setTitle('shopgate::cross-selling::export-as-property::product-id');
        $crossSellings = $this->crossSellingRoute->load(
            $event->getItem()->getId(),
            new Request(),
            $this->contextManager->getSalesContext(),
            $criteria
        )->getResult();
        $export = [];
        foreach ($crossSellings as $element) {
            if ($element->getProducts()->count() === 0) {
                continue;
            }
            $crossSellingEntity = $element->getCrossSelling();
            $property = $this->classFactory->createProperty()
                ->setLabel($crossSellingEntity->getTranslation('name'))
                ->setUid('relation-' . $crossSellingEntity->getId());
            $itemIds = [];
            foreach ($element->getProducts() as $product) {
                $id = $product->getParentId() ?: $product->getId();
                $itemIds[$id] = $id;
            }
            $property->setValue(implode(',', $itemIds));
            $export[] = $property;
        }

        $merged = array_merge($event->getProperties(), $export);
        $event->setProperties($merged);
    }
}
