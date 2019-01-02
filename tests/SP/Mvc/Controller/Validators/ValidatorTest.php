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

namespace SP\Tests\Mvc\Controller\Validators;

use PHPUnit\Framework\TestCase;
use SP\Mvc\Controller\Validators\Validator;

/**
 * Class ValidatorTest
 *
 * @package SP\Tests\Mvc\Controller\Validators
 */
class ValidatorTest extends TestCase
{
    const VALID_STRING = 'abcDE123_!';
    const VALID_REGEX = '^[a-zA-Z\d_!]+$';

    public function testMatchRegex()
    {
        $this->assertTrue(Validator::matchRegex(self::VALID_STRING, self::VALID_REGEX));

        $regex = '^[a-zA-Z\d]+$';

        $this->assertFalse(Validator::matchRegex(self::VALID_STRING, $regex));
    }

    public function testHasLetters()
    {
        $this->assertTrue(Validator::hasLetters(self::VALID_STRING));

        $string = '123_!';

        $this->assertFalse(Validator::hasLetters($string));
    }

    public function testHasUpper()
    {
        $this->assertTrue(Validator::hasUpper(self::VALID_STRING));

        $string = 'abc123_!';

        $this->assertFalse(Validator::hasUpper($string));
    }

    public function testHasSymbols()
    {
        $this->assertTrue(Validator::hasSymbols(self::VALID_STRING));

        $string = 'abcDE123';

        $this->assertFalse(Validator::hasSymbols($string));
    }

    public function testHasNumbers()
    {
        $this->assertTrue(Validator::hasNumbers(self::VALID_STRING));

        $string = 'abcDE_!';

        $this->assertFalse(Validator::hasNumbers($string));
    }

    public function testHasLower()
    {
        $this->assertTrue(Validator::hasLower(self::VALID_STRING));

        $string = 'DE123_!';

        $this->assertFalse(Validator::hasLower($string));
    }

    public function testIsRegex()
    {
        $this->assertNotFalse(Validator::isRegex(self::VALID_REGEX));

        $regex = '^[a-zA-Z\d+$';

        $this->assertFalse(Validator::isRegex($regex));
    }
}
