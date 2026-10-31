<?php
declare(strict_types=1);
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

namespace SP\Tests\Mvc\Controller\Validators;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\ItemPreset\Models\Password;
use SP\Modules\Web\Mvc\Controller\Validators\PasswordValidator;

/**
 * Class PasswordValidatorTest
 *
 * @package SP\Tests\Mvc\Controller\Validators
 */
#[Group('unitary')]
class PasswordValidatorTest extends TestCase
{
    /**
     * @var Password
     */
    private $password;

    /**
     * @throws ValidationException
     */
    #[Test]
    public function testValidate()
    {
        $validator = new PasswordValidator($this->password);
        $validator->validate($this->password, ValidatorTest::VALID_STRING);

        $this->assertTrue(true);
    }

    /**
     * @throws ValidationException
     */
    #[Test]
    public function testValidateNoLength()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to be 10 characters long');

        $validator = new PasswordValidator($this->password);
        $validator->validate($this->password, '12345678');
    }

    /**
     * @throws ValidationException
     */
    #[Test]
    public function testValidateNoLetters()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain letters');

        $validator = new PasswordValidator($this->password);
        $validator->validate($this->password, '1234567890');
    }

    /**
     * @throws ValidationException
     */
    #[Test]
    public function testValidateNoUpper()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain upper case letters');

        $validator = new PasswordValidator($this->password);
        $validator->validate($this->password, '1234567890abc');
    }

    /**
     * @throws ValidationException
     */
    #[Test]
    public function testValidateNoLower()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain lower case letters');

        $validator = new PasswordValidator($this->password);
        $validator->validate($this->password, '1234567890ABC');
    }

    /**
     * @throws ValidationException
     */
    #[Test]
    public function testValidateNoNumbers()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain numbers');

        $validator = new PasswordValidator($this->password);
        $validator->validate($this->password, 'ABCabcABCabcABC');
    }

    /**
     * @throws ValidationException
     */
    #[Test]
    public function testValidateNoSymbols()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password needs to contain symbols');

        $validator = new PasswordValidator($this->password);
        $validator->validate($this->password, '1234567890ABCabc');
    }

    /**
     * @throws ValidationException
     */
    #[Test]
    public function testValidateNoRegex()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password does not contain the required characters');

        $this->password = $this->buildPassword(ValidatorTest::VALID_REGEX);

        $validator = new PasswordValidator($this->password);
        $validator->validate($this->password, '1234567890ABCabc$');
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->password = $this->buildPassword();
    }

    private function buildPassword(?string $regex = null): Password
    {
        return new Password(
            length: 10,
            useNumbers: true,
            useLetters: true,
            useSymbols: true,
            useUpper: true,
            useLower: true,
            useImage: false,
            expireTime: 0,
            score: 0,
            regex: $regex
        );
    }
}
