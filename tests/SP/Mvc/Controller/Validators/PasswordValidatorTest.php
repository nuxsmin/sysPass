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
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ItemPreset\Password;
use SP\Mvc\Controller\Validators\PasswordValidator;

/**
 * Class PasswordValidatorTest
 *
 * @package SP\Tests\Mvc\Controller\Validators
 */
class PasswordValidatorTest extends TestCase
{
    /**
     * @var Password
     */
    private $password;

    /**
     * @throws ValidationException
     */
    public function testValidate()
    {
        $validator = new PasswordValidator($this->password);
        $validator->validate(ValidatorTest::VALID_STRING);

        $this->assertTrue(true);
    }

    /**
     * @throws ValidationException
     */
    public function testValidateNoLength()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to be 10 characters long');

        $validator = new PasswordValidator($this->password);
        $validator->validate('12345678');
    }

    /**
     * @throws ValidationException
     */
    public function testValidateNoLetters()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain letters');

        $validator = new PasswordValidator($this->password);
        $validator->validate('1234567890');
    }

    /**
     * @throws ValidationException
     */
    public function testValidateNoUpper()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain upper case letters');

        $validator = new PasswordValidator($this->password);
        $validator->validate('1234567890abc');
    }

    /**
     * @throws ValidationException
     */
    public function testValidateNoLower()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain lower case letters');

        $validator = new PasswordValidator($this->password);
        $validator->validate('1234567890ABC');
    }

    /**
     * @throws ValidationException
     */
    public function testValidateNoNumbers()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain numbers');

        $validator = new PasswordValidator($this->password);
        $validator->validate('ABCabcABCabcABC');
    }

    /**
     * @throws ValidationException
     */
    public function testValidateNoSymbols()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain symbols');

        $validator = new PasswordValidator($this->password);
        $validator->validate('1234567890ABCabc');
    }

    /**
     * @throws ValidationException
     */
    public function testValidateNoRegex()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password does not contain the required characters');

        $this->password->setRegex(ValidatorTest::VALID_REGEX);

        $validator = new PasswordValidator($this->password);
        $validator->validate('1234567890ABCabc$');
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->password = new Password();
        $this->password->setLength(10);
        $this->password->setUseLetters(true);
        $this->password->setUseNumbers(true);
        $this->password->setUseSymbols(true);
        $this->password->setUseUpper(true);
        $this->password->setUseLower(true);
    }
}
