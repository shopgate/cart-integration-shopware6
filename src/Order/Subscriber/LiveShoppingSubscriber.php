<?php

declare(strict_types=1);

namespace Shopgate\Shopware\Order\Subscriber;

use Shopgate\Shopware\Order\LineItem\Events\AfterIncItemMappingEvent;
use Shopgate\Shopware\Order\Quote\Events\BeforeAddLineItemsToQuote;
use Shopgate\Shopware\System\Configuration\ConfigBridge;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Content\Product\Cart\ProductCartProcessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LiveShoppingSubscriber implements EventSubscriberInterface
{
    private ConfigBridge $configBridge;

    public function __construct(ConfigBridge $configBridge)
    {
        $this->configBridge = $configBridge;
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
        $itemMap = $event->getMapping();
        $oldPrice = $itemMap->get('priceDefinition', []);
        $newPrice = [
            'type' => QuantityPriceDefinition::TYPE,
            'quantity' => (int)$incItem->getQuantity(),
            'price' => $incItem->getUnitAmount(),
            'taxRules' => [
                [
                    'taxRate' => $incItem->getTaxPercent(),
                    'percentage' => 100
                ]
            ]
        ];
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
