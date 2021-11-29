<?php
declare(strict_types=1);

namespace Shopgate\Shopware\Tests\Customer\Mapping;

use PHPUnit\Framework\TestCase;
use Shopgate\Shopware\Customer\Mapping\AddressMapping;
use Shopgate\Shopware\Customer\Mapping\LocationMapping;
use Shopgate\Shopware\Customer\Mapping\SalutationMapping;
use Shopgate\Shopware\System\CustomFields\CustomFieldMapping;
use ShopgateAddress;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;

class AddressMappingTest extends TestCase
{

    /**
     * @param $expected
     * @param string $defaultBilling
     * @param string $defaultShipping
     * @param string $addressId
     *
     * @dataProvider addressTypeProvider
     */
    public function testMapAddressType(
        $expected,
        string $defaultBilling,
        string $defaultShipping,
        string $addressId
    ): void
    {
        $customer = new CustomerEntity();
        $customer->setDefaultBillingAddressId($defaultBilling);
        $customer->setDefaultShippingAddressId($defaultShipping);

        $address = new CustomerAddressEntity();
        $address->setId($addressId);

        $location = $this->createMock(LocationMapping::class);
        $salute = $this->createMock(SalutationMapping::class);
        $cField = $this->createMock(CustomFieldMapping::class);
        $mapping = new AddressMapping($location, $salute, $cField);

        $result = $mapping->mapAddressType($customer, $address);
        $this->assertEquals($expected, $result);
    }

    public function addressTypeProvider(): array
    {
        return [
            'Billing address' => [
                'expected' => ShopgateAddress::INVOICE,
                'defaultBilling' => '12345',
                'defaultShipping' => '123456',
                'addressId' => '12345',
            ],
            'Shipping address' => [
                'expected' => ShopgateAddress::DELIVERY,
                'defaultBilling' => '123456',
                'defaultShipping' => '12345',
                'addressId' => '12345',
            ],
            'Both address' => [
                'expected' => ShopgateAddress::BOTH,
                'defaultBilling' => '123456',
                'defaultShipping' => '123456',
                'addressId' => '123456',
            ],
        ];
    }
}
