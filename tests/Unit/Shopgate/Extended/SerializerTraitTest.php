<?php declare(strict_types=1);

namespace Shopgate\Shopware\Tests\Unit\Shopgate\Extended;

use PHPUnit\Framework\TestCase;
use Shopgate\Shopware\Shopgate\Extended\ExtendedExternalCoupon;
use Shopgate\Shopware\Shopgate\Extended\SerializerTrait;
use ShopgateCart;
use ShopgateExternalCoupon;
use ShopgateOrder;
use ShopgateOrderItem;

class SerializerTraitTest extends TestCase
{

    /**
     * Testing that other classes can be extended and internal_info decoded
     *
     * @param SerializerTrait $class
     * @dataProvider internalInfoClassProvider
     */
    public function testGetUtilityInternalInfo(string $expected, $class): void
    {
        $this->assertEquals($expected, $class->getUtilityInternalInfo());
    }

    /**
     * Decoded info is a simple merge
     */
    public function testAddDecodedInfo(): void
    {
        $coupon = $this->createTraitMock(['internal_info' => '{"test2": true}']);
        $coupon->addDecodedInfo(['cartRule' => 'yes']);
        $this->assertEquals(['cartRule' => 'yes', 'test2' => true], $coupon->getDecodedInfo());

        $coupon2 = $this->createTraitMock(['internal_info' => null]);
        $coupon2->addDecodedInfo(['cartRule' => 'no']);
        $this->assertEquals(['cartRule' => 'no'], $coupon2->getDecodedInfo());

        $coupon2 = $this->createTraitMock();
        $coupon2->addDecodedInfo([]);
        $this->assertEquals([], $coupon2->getDecodedInfo());
    }

    /**
     * @return ShopgateExternalCoupon
     */
    private function createTraitMock(array $payload = [])
    {
        return new class ($payload) extends ShopgateExternalCoupon {
            use SerializerTrait;
        };
    }

    /**
     * Testing merging of decodedInfo and internalInfo when preparing json results
     */
    public function testToArray(): void
    {
        $coupon = $this->createTraitMock(['internal_info' => '{"test2": true}']);
        $coupon->addDecodedInfo(['itemType' => 'cartRule']);
        $result = $coupon->toArray();
        $this->assertJsonStringEqualsJsonString('{"itemType": "cartRule", "test2": true}', $result['internal_info']);

        $coupon2 = $this->createTraitMock(['internal_info' => null]);
        $coupon2->addDecodedInfo(['itemType' => 'cartRule']);
        $result2 = $coupon2->toArray();
        $this->assertJsonStringEqualsJsonString('{"itemType": "cartRule"}', $result2['internal_info']);

        $infoItem = new class (['internal_order_info' => 'test3']) extends ShopgateOrderItem {
            use SerializerTrait;
        };
        $infoItem->addDecodedInfo(['itemType' => 'cartRule']);
        $result3 = $infoItem->toArray();
        $this->assertJsonStringEqualsJsonString('{"itemType": "cartRule"}', $result3['internal_order_info']);

        // should not throw exception
        $noInternalInfo = new class () extends ShopgateOrder {
            use SerializerTrait;
        };
        $noInternalInfo->addDecodedInfo(['itemType' => 'cartRule']);
        $noInternalInfo->toArray();
    }

    /**
     * @return array[]
     */
    public function internalInfoClassProvider(): array
    {
        return [
            'shopgate cart class' => [
                'expected' => 'test',
                'class' => new class (['internal_cart_info' => 'test']) extends ShopgateCart {
                    use SerializerTrait;
                }
            ],
            'shopgate orderItem class' => [
                'expected' => 'test3',
                'class' => new class (['internal_order_info' => 'test3']) extends ShopgateOrderItem {
                    use SerializerTrait;
                }
            ],
            'extended coupon' => [
                'expected' => 'test2',
                'class' => new ExtendedExternalCoupon(['internal_info' => 'test2'])
            ]
        ];
    }
}
