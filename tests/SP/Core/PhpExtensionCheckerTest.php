<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Core;

use RuntimeException;
use SP\Core\Exceptions\CheckException;
use SP\Core\PhpExtensionChecker;
use SP\Tests\UnitaryTestCase;

/**
 * Class PhpExtensionCheckerTest
 *
 * @group unitary
 */
class PhpExtensionCheckerTest extends UnitaryTestCase
{

    public static function extensionMethodDataProvider(): array
    {
        return [
            ['checkCurl'],
            ['checkLdap'],
            ['checkPhar'],
            ['checkGd'],
        ];
    }

    /**
     * @dataProvider extensionMethodDataProvider
     *
     * @param string $method
     * @return void
     */
    public function testCallMagicMethod(string $method)
    {
        $phpExtensionChecker = new PhpExtensionChecker();

        $this->assertTrue($phpExtensionChecker->{$method}());
    }

    public function testCallMagicMethodWithUnknownMethod()
    {
        $phpExtensionChecker = new PhpExtensionChecker();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown magic method');

        $phpExtensionChecker->test();
    }

    public function testCallMagicMethodWithUnknownExtension()
    {
        $phpExtensionChecker = new PhpExtensionChecker();

        self::assertFalse($phpExtensionChecker->checkTest());
    }

    public function testCheckMandatory()
    {
        $phpExtensionChecker = new PhpExtensionChecker();
        $phpExtensionChecker->checkMandatory();

        $this->assertTrue(true);
    }

    /**
     * @throws CheckException
     */
    public function testCheckIsAvailable()
    {
        $phpExtensionChecker = new PhpExtensionChecker();

        $this->assertTrue($phpExtensionChecker->checkIsAvailable('curl'));
        $this->assertTrue($phpExtensionChecker->checkIsAvailable('ldap'));
        $this->assertTrue($phpExtensionChecker->checkIsAvailable('gd'));
        $this->assertTrue($phpExtensionChecker->checkIsAvailable('phar'));
    }

    /**
     * @throws CheckException
     */
    public function testCheckIsAvailableWithUnknown()
    {
        $phpExtensionChecker = new PhpExtensionChecker();

        $this->assertFalse($phpExtensionChecker->checkIsAvailable('test'));
    }

    /**
     * @throws CheckException
     */
    public function testCheckIsAvailableWithUnknownAndException()
    {
        $phpExtensionChecker = new PhpExtensionChecker();

        $this->expectException(CheckException::class);
        $this->expectExceptionMessageMatches('/^Oops, it seems that some extensions are not available: \'\w+\'$/');

        $this->assertFalse($phpExtensionChecker->checkIsAvailable('test', true));
    }

    public function testGetMissing()
    {
        $phpExtensionChecker = new PhpExtensionChecker();
        $out = $phpExtensionChecker->getMissing();

        $this->assertCount(0, $out);
    }
}
