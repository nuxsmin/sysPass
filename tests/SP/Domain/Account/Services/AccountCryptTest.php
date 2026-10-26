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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Domain\Account\Services\AccountCrypt;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountCryptServiceTest
 *
 */
#[Group('unitary')]
class AccountCryptTest extends UnitaryTestCase
{
    private AccountCrypt              $accountCrypt;
    private MockObject|CryptInterface $crypt;

    /**
     * @throws ServiceException
     */
    public function testGetPasswordEncrypted(): void
    {
        $pass = self::$faker->password;
        $key = self::$faker->password;
        $masterPass = self::$faker->password;

        $this->crypt->expects(self::once())
                    ->method('makeSecuredKey')
                    ->with($masterPass)
                    ->willReturn($key);

        $this->crypt->expects(self::once())
                    ->method('encrypt')
                    ->with($pass)
                    ->willReturn($pass);

        $out = $this->accountCrypt->getPasswordEncrypted($pass, $masterPass);

        $this->assertEquals($pass, $out->getPass());
        $this->assertEquals($key, $out->getKey());
    }

    /**
     * @throws ServiceException
     * @throws ContextException
     */
    public function testGetPasswordEncryptedThrowsExceptionWithNoMasterPassword(): void
    {
        $this->context->setTrasientKey('_masterpass', '');

        $this->crypt->expects(self::never())
                    ->method('makeSecuredKey');

        $this->crypt->expects(self::never())
                    ->method('encrypt');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while retrieving master password from context');

        $this->accountCrypt->getPasswordEncrypted(self::$faker->password);
    }

    /**
     * @throws ServiceException
     */
    public function testGetPasswordEncryptedThrowsExceptionWithEmptyMasterPassword(): void
    {
        $this->crypt->expects(self::never())
                    ->method('makeSecuredKey');

        $this->crypt->expects(self::never())
                    ->method('encrypt');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Master password not set');

        $this->accountCrypt->getPasswordEncrypted(self::$faker->password, '');
    }

    /**
     * @throws ServiceException
     */
    public function testGetPasswordEncryptedThrowsExceptionWithLongPass(): void
    {
        $this->crypt->expects(self::once())
                    ->method('makeSecuredKey')
                    ->willReturn(self::$faker->password);

        $this->crypt->expects(self::once())
                    ->method('encrypt')
                    ->willReturn(self::$faker->text(1500));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->accountCrypt->getPasswordEncrypted(self::$faker->password, self::$faker->password);
    }

    /**
     * @throws ServiceException
     */
    public function testGetPasswordEncryptedThrowsExceptionWithLongKey(): void
    {
        $this->crypt->expects(self::once())
                    ->method('makeSecuredKey')
                    ->willReturn(self::$faker->text(1500));

        $this->crypt->expects(self::once())
                    ->method('encrypt')
                    ->willReturn(self::$faker->password);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->accountCrypt->getPasswordEncrypted(self::$faker->password, self::$faker->password);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->crypt = $this->createMock(CryptInterface::class);

        $this->accountCrypt = new AccountCrypt($this->application, $this->crypt);
    }
}
