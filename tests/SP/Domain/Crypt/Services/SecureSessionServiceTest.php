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

namespace SP\Tests\Domain\Crypt\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\UuidCookie;
use SP\Core\Crypt\Vault;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\RequestBasedPasswordInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Crypt\Services\SecureSessionService;
use SP\Infrastructure\File\FileCacheInterface;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;

/**
 * Class SecureSessionServiceTest
 *
 * @group unitary
 */
class SecureSessionServiceTest extends UnitaryTestCase
{
    private SecureSessionService                     $secureSessionService;
    private RequestBasedPasswordInterface|MockObject $requestBasedPassword;
    private CryptInterface|MockObject                $crypt;
    private FileCacheInterface|MockObject            $fileCache;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws EnvironmentIsBrokenException
     * @throws CryptException
     */
    public function testGetKey()
    {
        $securedKey = Key::createNewRandomKey();
        $key = self::$faker->password;
        $vault = Vault::factory($this->crypt)->saveData($securedKey->saveToAsciiSafeString(), $key);

        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(false);
        $this->fileCache->expects(self::once())->method('load')->willReturn($vault);
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn($key);

        $this->assertInstanceOf(Key::class, $this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetKeyCacheExpired()
    {
        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(true);
        $this->fileCache->expects(self::once())->method('save');
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn(self::$faker->password);

        $this->assertInstanceOf(Key::class, $this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetKeyFileErrorCheckingExpire()
    {
        $this->fileCache->expects(self::once())->method('isExpired')->willThrowException(new FileException('test'));
        $this->fileCache->expects(self::once())->method('save');
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn(self::$faker->password);

        $this->assertInstanceOf(Key::class, $this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetKeyFileErrorLoading()
    {
        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(false);
        $this->fileCache->expects(self::once())->method('load')->willThrowException(new FileException('test'));
        $this->fileCache->expects(self::once())->method('save');
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn(self::$faker->password);

        $this->assertInstanceOf(Key::class, $this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetKeyFileErrorSaving()
    {
        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(true);
        $this->fileCache->expects(self::once())->method('save')->willThrowException(new FileException('test'));
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn(self::$faker->password);

        $this->assertFalse($this->secureSessionService->getKey());
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws CryptException
     */
    public function testGetKeyBuildPasswordException()
    {
        $securedKey = Key::createNewRandomKey();
        $key = self::$faker->password;
        $vault = Vault::factory($this->crypt)->saveData($securedKey->saveToAsciiSafeString(), $key);

        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(false);
        $this->fileCache->expects(self::once())->method('load')->willReturn($vault);
        $this->requestBasedPassword->expects(self::once())->method('build')->willThrowException(new Exception());

        $this->assertFalse($this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ServiceException
     */
    public function testGetFileNameFrom()
    {
        $uuidCookie = $this->createMock(UuidCookie::class);

        $uuidCookie->method('load')
                   ->willReturn(uniqid('', true));
        $uuidCookie->method('create')
                   ->willReturn(uniqid('', true));

        $this->assertNotEmpty(SecureSessionService::getFileNameFrom($uuidCookie, self::$faker->password));
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ServiceException
     */
    public function testGetFileNameFromErrorLoadingCookie()
    {
        $uuidCookie = $this->createMock(UuidCookie::class);

        $uuidCookie->method('load')->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to get UUID for filename');

        SecureSessionService::getFileNameFrom($uuidCookie, self::$faker->password);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ServiceException
     */
    public function testGetFileNameFromErrorCreatingCookie()
    {
        $uuidCookie = $this->createMock(UuidCookie::class);

        $uuidCookie->method('create')->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to get UUID for filename');

        SecureSessionService::getFileNameFrom($uuidCookie, self::$faker->password);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ContextException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->crypt = new Crypt();
        $this->requestBasedPassword = $this->createMock(RequestBasedPasswordInterface::class);
        $this->fileCache = $this->createMock(FileCacheInterface::class);

        $this->secureSessionService = new SecureSessionService(
            $this->application,
            $this->crypt,
            $this->fileCache,
            $this->requestBasedPassword
        );
    }

}
