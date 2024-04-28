<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SPT\Domain\Http\Adapters;

use Faker\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SPT\UnitaryTestCase;

/**
 * Class AddressTest
 *
 */
#[Group('unitary')]
class AddressTest extends UnitaryTestCase
{

    public static function binaryCheckProvider(): array
    {
        $faker = Factory::create();

        return array_map(fn() => [$faker->ipv4], [$faker->ipv6], range(0, 99));
    }

    public static function checkAddressProvider(): array
    {
        return [
            ['192.168.0.1', '192.168.0.0', '255.255.255.0', true],
            ['192.168.0.1', '192.168.0.0', '255.255.0.0', true],
            ['192.168.0.1', '192.168.0.0', '255.0.0.0', true],
            ['192.168.0.1', '192.168.1.0', '255.255.255.0', false],
            ['192.168.0.1', '172.168.0.1', '255.255.0.0', false],
            ['192.168.0.1', '10.0.0.1', '255.0.0.0', false],
        ];
    }

    public static function checkAddressCidrProvider(): array
    {
        return [
            ['192.168.0.1', '192.168.0.0', '24', true],
            ['192.168.0.1', '192.168.0.0', '16', true],
            ['192.168.0.1', '192.168.0.0', '8', true],
            ['192.168.0.1', '192.168.1.0', '24', false],
            ['192.168.0.1', '172.168.0.1', '16', false],
            ['192.168.0.1', '10.0.0.1', '8', false],
        ];
    }

    public static function checkCidrProvider(): array
    {
        return [
            [32, '255.255.255.255'],
            [31, '255.255.255.254'],
            [30, '255.255.255.252'],
            [29, '255.255.255.248'],
            [28, '255.255.255.240'],
            [27, '255.255.255.224'],
            [26, '255.255.255.192'],
            [25, '255.255.255.128'],
            [24, '255.255.255.0'],
            [23, '255.255.254.0'],
            [22, '255.255.252.0'],
            [21, '255.255.248.0'],
            [20, '255.255.240.0'],
            [19, '255.255.224.0'],
            [18, '255.255.192.0'],
            [17, '255.255.128.0'],
            [16, '255.255.0.0'],
            [15, '255.254.0.0'],
            [14, '255.252.0.0'],
            [13, '255.248.0.0'],
            [12, '255.240.0.0'],
            [11, '255.224.0.0'],
            [10, '255.192.0.0'],
            [9, '255.128.0.0'],
            [8, '255.0.0.0'],
            [7, '254.0.0.0'],
            [6, '252.0.0.0'],
            [5, '248.0.0.0'],
            [4, '240.0.0.0'],
            [3, '224.0.0.0'],
            [2, '192.0.0.0'],
            [1, '128.0.0.0'],
            [0, '0.0.0.0'],
        ];
    }

    /**
     * @param string $address
     *
     * @throws InvalidArgumentException
     */
    #[DataProvider('binaryCheckProvider')]
    public function testToBinary(string $address)
    {
        $binary = \SP\Domain\Http\Adapters\Address::toBinary($address);

        $this->assertNotEmpty($binary);
        $this->assertEquals($address, \SP\Domain\Http\Adapters\Address::fromBinary($binary));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testToBinaryInvalidIpv4()
    {
        $this->expectException(InvalidArgumentException::class);

        \SP\Domain\Http\Adapters\Address::toBinary('192.168.0.256');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testToBinaryInvalidIpv6()
    {
        $this->expectException(InvalidArgumentException::class);

        \SP\Domain\Http\Adapters\Address::toBinary('1200::AB00:1234::2552:7777:1313');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFromBinaryWithIpv4()
    {
        $address = self::$faker->ipv4;

        $out = \SP\Domain\Http\Adapters\Address::fromBinary(inet_pton($address));

        $this->assertEquals($address, $out);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFromBinaryWithIpv6()
    {
        $address = self::$faker->ipv6;

        $out = \SP\Domain\Http\Adapters\Address::fromBinary(inet_pton($address));

        $this->assertEquals($address, $out);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFromBinaryWithInvalidAddress()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP');

        \SP\Domain\Http\Adapters\Address::fromBinary('something');
    }

    /**
     * @param string $address
     * @param string $inAddress
     * @param string $inMask
     * @param bool $expected
     *
     * @throws InvalidArgumentException
     */
    #[DataProvider('checkAddressProvider')]
    public function testCheck(string $address, string $inAddress, string $inMask, bool $expected)
    {
        $this->assertEquals($expected, \SP\Domain\Http\Adapters\Address::check($address, $inAddress, $inMask));
    }

    /**
     * @param string $address
     * @param string $inAddress
     * @param string $inMask
     * @param bool $expected
     *
     * @throws InvalidArgumentException
     */
    #[DataProvider('checkAddressCidrProvider')]
    public function testCheckWithCidr(string $address, string $inAddress, string $inMask, bool $expected)
    {
        $this->assertEquals(
            $expected,
            \SP\Domain\Http\Adapters\Address::check(
                $address,
                $inAddress,
                \SP\Domain\Http\Adapters\Address::cidrToDec($inMask)
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCheckWithInvalidAddress()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP');

        \SP\Domain\Http\Adapters\Address::check('123', '123', '123');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParseWithFullMask()
    {
        $address = '192.168.0.1/255.255.255.0';
        $parse = \SP\Domain\Http\Adapters\Address::parse4($address);

        $this->assertCount(5, $parse);
        $this->assertArrayHasKey('address', $parse);
        $this->assertEquals('192.168.0.1', $parse['address']);
        $this->assertArrayHasKey('mask', $parse);
        $this->assertEquals('255.255.255.0', $parse['mask']);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParseWithoutMask()
    {
        $address = '192.168.0.2';
        $parse = \SP\Domain\Http\Adapters\Address::parse4($address);

        $this->assertCount(3, $parse);
        $this->assertArrayHasKey('address', $parse);
        $this->assertEquals('192.168.0.2', $parse['address']);

        $address = '192.168.0.1/24';
        $parse = \SP\Domain\Http\Adapters\Address::parse4($address);

        $this->assertCount(7, $parse);
        $this->assertArrayHasKey('address', $parse);
        $this->assertEquals('192.168.0.1', $parse['address']);
        $this->assertArrayHasKey('cidr', $parse);
        $this->assertEquals('24', $parse['cidr']);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParseWithCIDR()
    {
        $address = '192.168.0.1/24';
        $parse = \SP\Domain\Http\Adapters\Address::parse4($address);

        $this->assertCount(7, $parse);
        $this->assertArrayHasKey('address', $parse);
        $this->assertEquals('192.168.0.1', $parse['address']);
        $this->assertArrayHasKey('cidr', $parse);
        $this->assertEquals('24', $parse['cidr']);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParseInvalidIp()
    {
        $this->expectException(InvalidArgumentException::class);

        $address = '192.168.0.1000/255.255.255.0';
        \SP\Domain\Http\Adapters\Address::parse4($address);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParseInvalidMask()
    {
        $this->expectException(InvalidArgumentException::class);

        $address = '192.168.0.100/255.255.2500.0';
        \SP\Domain\Http\Adapters\Address::parse4($address);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParseInvalidCidr()
    {
        $this->expectException(InvalidArgumentException::class);

        $address = '192.168.0.100/100';
        \SP\Domain\Http\Adapters\Address::parse4($address);
    }

    /**
     * @param $cidr
     * @param $mask
     */
    #[DataProvider('checkCidrProvider')]
    public function testCidrToDec($cidr, $mask)
    {
        $this->assertEquals($mask, \SP\Domain\Http\Adapters\Address::cidrToDec($cidr));
    }
}
