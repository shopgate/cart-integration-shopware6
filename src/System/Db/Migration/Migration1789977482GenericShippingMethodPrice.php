<?php declare(strict_types=1);

namespace Shopgate\Shopware\System\Db\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethod;
use Shopgate\Shopware\System\Db\Shipping\GenericShippingMethodPrice;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryCalculator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Backfills the price of the generic shipping method, required by Shopware 6.7.14+ for active methods
 */
class Migration1789977482GenericShippingMethodPrice extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1789977482;
    }

    /**
     * @throws DBALException
     */
    public function update(Connection $connection): void
    {
        $methodId = Uuid::fromHexToBytes(GenericShippingMethod::UUID);

        // removed by the merchant
        if (!$connection->fetchOne('SELECT 1 FROM shipping_method WHERE id = :id', ['id' => $methodId])) {
            return;
        }
        // fresh install or merchant added one
        if ($connection->fetchOne('SELECT 1 FROM shipping_method_price WHERE shipping_method_id = :id', ['id' => $methodId])) {
            return;
        }

        $connection->insert('shipping_method_price', [
            'id' => Uuid::fromHexToBytes(GenericShippingMethodPrice::UUID),
            'shipping_method_id' => $methodId,
            'calculation' => DeliveryCalculator::CALCULATION_BY_PRICE,
            'quantity_start' => 0,
            'currency_price' => json_encode([
                'c' . Defaults::CURRENCY => [
                    'currencyId' => Defaults::CURRENCY,
                    'net' => 0.0,
                    'gross' => 0.0,
                    'linked' => false,
                    'listPrice' => null,
                ],
            ]),
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }
}
