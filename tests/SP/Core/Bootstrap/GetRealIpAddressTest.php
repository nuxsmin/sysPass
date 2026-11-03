<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Core\Bootstrap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function SP\Tests\getRealIpAddress;
use function SP\Tests\getRealIpAddressFromOutput;

/**
 * Class GetRealIpAddressTest
 */
#[Group('unitary')]
class GetRealIpAddressTest extends TestCase
{
    public static function outputProvider(): array
    {
        return [
            'valid address' => ['203.0.113.10', '203.0.113.10'],
            'address with CIDR' => ['203.0.113.10/24', '203.0.113.10'],
            'surrounding whitespace' => ["  203.0.113.10\n", '203.0.113.10'],
            'null shell output' => [null, null],
            'empty shell output' => ['', null],
            'blank shell output' => ["\n", null],
            'invalid shell output' => ['Device "eth0" does not exist.', null],
        ];
    }

    #[Test]
    #[DataProvider('outputProvider')]
    public function testGetRealIpAddressFromOutput(?string $output, ?string $expected): void
    {
        $address = getRealIpAddressFromOutput($output);

        self::assertNotFalse(filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));

        if ($expected !== null) {
            self::assertSame($expected, $address);
        }
    }

    #[Test]
    public function testSelfIpAddressIsValidIpv4(): void
    {
        self::assertTrue(defined('SELF_IP_ADDRESS'));
        self::assertNotFalse(
            filter_var(SELF_IP_ADDRESS, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        );
    }

    #[Test]
    public function testGetRealIpAddressReturnsValidIpv4(): void
    {
        self::assertNotFalse(
            filter_var(getRealIpAddress(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        );
    }
}
