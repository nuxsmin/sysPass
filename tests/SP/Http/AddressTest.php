<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Http;

use Faker\Factory;
use PHPUnit\Framework\TestCase;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Http\Address;

/**
 * Class AddressTest
 *
 * @package SP\Tests\Http
 */
class AddressTest extends TestCase
{

    /**
     * @dataProvider binaryCheckProvider
     *
     * @param string $address
     *
     * @throws InvalidArgumentException
     */
    public function testBinary($address)
    {
        $binary = Address::toBinary($address);

        $this->assertNotEmpty($binary);
        $this->assertEquals($address, Address::fromBinary($binary));
    }

    /**
     * @return array
     */
    public function binaryCheckProvider()
    {
        $faker = Factory::create();

        $out = [];

        for ($i = 0; $i <= 100; $i++) {
            $out[] = [$faker->ipv4];
            $out[] = [$faker->ipv6];
        }

        return $out;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBinaryInvalidIpv4()
    {
        $this->expectException(InvalidArgumentException::class);

        Address::toBinary('192.168.0.256');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testBinaryInvalidIpv6()
    {
        $this->expectException(InvalidArgumentException::class);

        Address::toBinary('1200::AB00:1234::2552:7777:1313');
    }

    /**
     * @dataProvider checkAddressProvider
     *
     * @param string $address
     * @param string $inAddress
     * @param string $inMask
     * @param bool   $expected
     *
     * @throws InvalidArgumentException
     */
    public function testCheck($address, $inAddress, $inMask, $expected)
    {
        $this->assertEquals($expected, Address::check($address, $inAddress, $inMask));
    }

    /**
     * @dataProvider checkAddressCidrProvider
     *
     * @param string $address
     * @param string $inAddress
     * @param string $inMask
     * @param bool   $expected
     *
     * @throws InvalidArgumentException
     */
    public function testCheckWithCidr($address, $inAddress, $inMask, $expected)
    {
        $this->assertEquals($expected, Address::check($address, $inAddress, Address::cidrToDec($inMask)));
    }

    /**
     * @return array
     */
    public function checkAddressProvider()
    {
        return [
            ['192.168.0.1', '192.168.0.0', '255.255.255.0', true],
            ['192.168.0.1', '192.168.0.0', '255.255.0.0', true],
            ['192.168.0.1', '192.168.0.0', '255.0.0.0', true],
            ['192.168.0.1', '192.168.1.0', '255.255.255.0', false],
            ['192.168.0.1', '172.168.0.1', '255.255.0.0', false],
            ['192.168.0.1', '10.0.0.1', '255.0.0.0', false]
        ];
    }

    /**
     * @return array
     */
    public function checkAddressCidrProvider()
    {
        return [
            ['192.168.0.1', '192.168.0.0', '24', true],
            ['192.168.0.1', '192.168.0.0', '16', true],
            ['192.168.0.1', '192.168.0.0', '8', true],
            ['192.168.0.1', '192.168.1.0', '24', false],
            ['192.168.0.1', '172.168.0.1', '16', false],
            ['192.168.0.1', '10.0.0.1', '8', false]
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParse()
    {
        $address = '192.168.0.1/255.255.255.0';
        $parse = Address::parse4($address);

        $this->assertCount(5, $parse);
        $this->assertArrayHasKey('address', $parse);
        $this->assertEquals('192.168.0.1', $parse['address']);
        $this->assertArrayHasKey('mask', $parse);
        $this->assertEquals('255.255.255.0', $parse['mask']);

        $address = '192.168.0.2';
        $parse = Address::parse4($address);

        $this->assertCount(3, $parse);
        $this->assertArrayHasKey('address', $parse);
        $this->assertEquals('192.168.0.2', $parse['address']);

        $address = '192.168.0.1/24';
        $parse = Address::parse4($address);

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
        Address::parse4($address);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParseInvalidMask()
    {
        $this->expectException(InvalidArgumentException::class);

        $address = '192.168.0.100/255.255.2500.0';
        Address::parse4($address);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testParseInvalidCidr()
    {
        $this->expectException(InvalidArgumentException::class);

        $address = '192.168.0.100/100';
        Address::parse4($address);
    }

    /**
     * @dataProvider checkCidrProvider
     *
     * @param $cidr
     * @param $mask
     */
    public function testCidrToDec($cidr, $mask)
    {
        $this->assertEquals($mask, Address::cidrToDec($cidr));
    }

    /**
     * @return array
     */
    public function checkCidrProvider()
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
            [0, '0.0.0.0']
        ];
    }
}
