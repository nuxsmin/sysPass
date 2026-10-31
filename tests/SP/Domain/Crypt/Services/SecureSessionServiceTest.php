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

namespace SP\Tests\Domain\Crypt\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Bootstrap\Path;
use SP\Domain\Common\Adapters\LegacyClassMap;
use SP\Domain\Common\Adapters\SerializedPayloadRewriter;
use SP\Domain\Core\Context\ContextException;
use SP\Domain\Core\Crypt\Crypt;
use SP\Core\Crypt\UuidCookie;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\RequestBasedPasswordInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Crypt\Services\SecureSession;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Domain\Core\Exceptions\FileException;
use SP\Infrastructure\File\FileCache;
use SP\Tests\UnitaryTestCase;

/**
 * Class SecureSessionServiceTest
 *
 */
#[Group('unitary')]
class SecureSessionServiceTest extends UnitaryTestCase
{
    private SecureSession $secureSessionService;
    private RequestBasedPasswordInterface|MockObject $requestBasedPassword;
    private CryptInterface|MockObject                $crypt;
    private FileCacheService|MockObject $fileCache;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws EnvironmentIsBrokenException
     * @throws CryptException
     */
    #[Test]
    public function testGetKey()
    {
        $securedKey = Key::createNewRandomKey();
        $key = self::$faker->password;
        $vault = Vault::factory($this->crypt)->saveData($securedKey->saveToAsciiSafeString(), $key);

        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(false);
        $this->fileCache->expects(self::once())->method('loadWith')->willReturn($vault);
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn($key);

        $this->assertInstanceOf(Key::class, $this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
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
    #[Test]
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
    #[Test]
    public function testGetKeyFileErrorLoading()
    {
        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(false);
        $this->fileCache->expects(self::once())->method('loadWith')->willThrowException(new FileException('test'));
        $this->fileCache->expects(self::once())->method('save');
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn(self::$faker->password);

        $this->assertInstanceOf(Key::class, $this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
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
    #[Test]
    public function testGetKeyBuildPasswordException()
    {
        $securedKey = Key::createNewRandomKey();
        $key = self::$faker->password;
        $vault = Vault::factory($this->crypt)->saveData($securedKey->saveToAsciiSafeString(), $key);

        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(false);
        $this->fileCache->expects(self::once())->method('loadWith')->willReturn($vault);
        $this->requestBasedPassword->expects(self::once())->method('build')->willThrowException(new Exception());

        $this->assertFalse($this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testGetKeyInvalidClassRegenerates()
    {
        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(false);
        $this->fileCache->expects(self::once())
                        ->method('loadWith')
                        ->willThrowException(InvalidClassException::error('test'));
        $this->fileCache->expects(self::once())->method('save');
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn(self::$faker->password);

        $this->assertInstanceOf(Key::class, $this->secureSessionService->getKey());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testGetKeyInvalidClassSaveFails()
    {
        $this->fileCache->expects(self::once())->method('isExpired')->willReturn(false);
        $this->fileCache->expects(self::once())
                        ->method('loadWith')
                        ->willThrowException(InvalidClassException::error('test'));
        $this->fileCache->expects(self::once())->method('save')->willThrowException(new FileException('test'));
        $this->requestBasedPassword->expects(self::once())->method('build')->willReturn(self::$faker->password);

        $this->assertFalse($this->secureSessionService->getKey());
    }

    /**
     * @throws CryptException
     */
    #[Test]
    public function testGetKeyRegeneratesLegacyCacheFile()
    {
        $file = tempnam(TMP_PATH, 'secure-session-');
        $password = self::$faker->password;

        $legacy = SerializedPayloadRewriter::rewrite(
            serialize(Vault::factory($this->crypt)->saveData(self::$faker->password, $password)),
            array_flip(LegacyClassMap::MAP)
        );
        file_put_contents($file, $legacy);

        $this->requestBasedPassword->method('build')->willReturn($password);

        $secureSession = new SecureSession(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->crypt,
            new FileCache($file),
            $this->requestBasedPassword
        );

        $key = $secureSession->getKey();

        $this->assertInstanceOf(Key::class, $key);
        $this->assertSame(
            $key->saveToAsciiSafeString(),
            (new FileCache($file))->loadWith(Vault::class)->getData($password)
        );

        unlink($file);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ServiceException
     */
    #[Test]
    public function testGetFileNameFrom()
    {
        $uuidCookie = $this->createMock(UuidCookie::class);

        $uuidCookie->method('load')
                   ->willReturn(uniqid('', true));
        $uuidCookie->method('create')
                   ->willReturn(uniqid('', true));

        $this->assertNotEmpty(
            SecureSession::getFileNameFrom($uuidCookie, self::$faker->password, $this->pathsContext[Path::CACHE])
        );
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ServiceException
     */
    #[Test]
    public function testGetFileNameFromErrorLoadingCookie()
    {
        $uuidCookie = $this->createMock(UuidCookie::class);

        $uuidCookie->method('load')->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to get UUID for filename');

        SecureSession::getFileNameFrom($uuidCookie, self::$faker->password, $this->pathsContext[Path::CACHE]);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws ServiceException
     */
    #[Test]
    public function testGetFileNameFromErrorCreatingCookie()
    {
        $uuidCookie = $this->createMock(UuidCookie::class);

        $uuidCookie->method('create')->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to get UUID for filename');

        SecureSession::getFileNameFrom($uuidCookie, self::$faker->password, $this->pathsContext[Path::CACHE]);
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
        $this->fileCache = $this->createMock(FileCacheService::class);

        $this->secureSessionService = new SecureSession(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->crypt,
            $this->fileCache,
            $this->requestBasedPassword
        );
    }

}
