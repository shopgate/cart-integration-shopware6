<?php /** @noinspection PhpCastIsUnnecessaryInspection */

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Order\LineItem\Events\AfterIncItemMappingEvent;
use Shopgate\Shopware\Order\Quote\Events\BeforeAddLineItemsToQuote;
use Shopgate\Shopware\Order\Taxes\TaxMapping;
use Shopgate\Shopware\Shopgate\RequestPersist;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Content\Product\Cart\ProductCartProcessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LiveShoppingSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly ConfigBridge   $configBridge,
        private readonly RequestPersist $requestPersist,
        private readonly TaxMapping $taxMapping
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterIncItemMappingEvent::class => 'addPriceDefinitions',
            BeforeAddLineItemsToQuote::class => 'addPermissions'
        ];
    }

    /**
     * We respect any rewrites be merging 'theirs' into ours
     */
    public function addPriceDefinitions(AfterIncItemMappingEvent $event): void
    {
        if (!$this->configBridge->get(ConfigBridge::SYSTEM_CONFIG_IS_LIVE_SHOPPING)) {
            return;
        }
        $incItem = $event->getItem();
        $ids = $this->requestPersist->getEntity()->getItemIds();
        $tax = (float)$incItem->getTaxPercent();
        $newPrice = [
            'type' => QuantityPriceDefinition::TYPE,
            'quantity' => (int)$incItem->getQuantity(),
            'price' => $incItem->getUnitAmount(), // Net price as we calculate tax manually
            'taxRules' => empty($tax)
                ? $this->taxMapping->mapTaxRate($incItem, $ids, $event->getContext())
                : [['taxRate' => $tax, 'percentage' => 100]]
        ];

        $itemMap = $event->getMapping();
        $oldPrice = $itemMap->get('priceDefinition', []);
        $itemMap->set('priceDefinition', array_merge($newPrice, $oldPrice));
    }

    /**
     * Permissions are necessary to be able to rewrite product prices
     */
    public function addPermissions(BeforeAddLineItemsToQuote $event): void
    {
        if (!$this->configBridge->get(ConfigBridge::SYSTEM_CONFIG_IS_LIVE_SHOPPING)) {
            return;
        }
        $context = $event->getContext();
        $context->setPermissions(array_merge(
            $context->getPermissions(),
            [ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true]
        ));
    }
}
