<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Domain\Crypt\Services;

use Closure;
use Dotenv\Repository\RepositoryInterface;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Ports\MasterPasswordStorageGuard;
use SP\Domain\Crypt\Services\MasterPass;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Tests\UnitaryTestCase;

/**
 * Class MasterPassTest
 *
 */
#[Group('unitary')]
class MasterPassTest extends UnitaryTestCase
{

    private ConfigService|MockObject                $configService;
    private AccountMasterPasswordService|MockObject $accountMasterPasswordService;
    private CustomFieldCryptService|MockObject      $customFieldCryptService;
    private MockObject|RepositoryInterface          $repository;
    private MasterPass                              $masterPass;

    #[Test]
    public function testCheckUserUpdateMPassWithFutureTime()
    {
        $now = time();

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('lastupdatempass')
            ->willReturn((string)$now);

        $this->assertTrue($this->masterPass->checkUserUpdateMPass($now + 3600));
    }

    #[Test]
    public function testCheckUserUpdateMPassWithPastTime()
    {
        $now = time();

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('lastupdatempass')
            ->willReturn((string)$now);

        $this->assertFalse($this->masterPass->checkUserUpdateMPass($now - 3600));
    }

    #[Test]
    public function testCheckUserUpdateMPassWithEqualTime()
    {
        $now = time();

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('lastupdatempass')
            ->willReturn((string)$now);

        $this->assertTrue($this->masterPass->checkUserUpdateMPass($now));
    }

    #[Test]
    public function testCheckUserUpdateMPassWithServiceError()
    {
        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('lastupdatempass')
            ->willThrowException(ServiceException::error('test'));

        $this->assertTrue($this->masterPass->checkUserUpdateMPass(time()));
    }

    #[Test]
    public function testCheckUserUpdateMPassWithNoSuchItemError()
    {
        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('lastupdatempass')
            ->willThrowException(NoSuchItemException::error('test'));

        $this->assertTrue($this->masterPass->checkUserUpdateMPass(time()));
    }

    #[Test]
    public function testCheckMasterPassword()
    {
        $password = self::$faker->sha1();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('masterPwd')
            ->willReturn($hash);

        self::assertTrue($this->masterPass->checkMasterPassword($password));
    }

    #[Test]
    public function testCheckMasterPasswordWithServiceError()
    {
        $password = self::$faker->sha1();

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('masterPwd')
            ->willThrowException(ServiceException::error('test'));

        self::assertFalse($this->masterPass->checkMasterPassword($password));
    }

    #[Test]
    public function testCheckMasterPasswordWithNoSuchItemError()
    {
        $password = self::$faker->sha1();

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('masterPwd')
            ->willThrowException(NoSuchItemException::error('test'));

        self::assertFalse($this->masterPass->checkMasterPassword($password));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function testChangeMasterPassword()
    {
        $hash = self::$faker->sha1();

        $this->repository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::withResolveCallableCallback());

        $request = new UpdateMasterPassRequest('123', '456', $hash);

        $this->accountMasterPasswordService
            ->expects(self::once())
            ->method('updateMasterPassword')
            ->with($request);
        $this->accountMasterPasswordService
            ->expects(self::once())
            ->method('updateHistoryMasterPassword')
            ->with($request);
        $this->customFieldCryptService
            ->expects(self::once())
            ->method('updateMasterPassword')
            ->with($request);

        $this->configService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(
                ...
                self::withConsecutive(['masterPwd', $request->getHash()], ['lastupdatempass', self::anything()])
            )->willReturn(true);

        $this->masterPass->changeMasterPassword($request);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    #[Test]
    public function testChangeClearsTemporaryPasswordMaterial(): void
    {
        $this->repository->method('transactionAware')->willReturnCallback(
            static function (Closure $operation, object $service): void {
                $operation->call($service);
            }
        );
        $this->configService->method('getByParam')->willReturn('1');
        $saved = [];
        $this->configService->method('save')->willReturnCallback(
            static function (string $key, string $value) use (&$saved): bool {
                $saved[$key] = $value;
                return true;
            }
        );
        $this->masterPass->changeMasterPassword(new UpdateMasterPassRequest('old', 'new', 'hash'));
        self::assertSame('', $saved['tempmaster_pass'] ?? null);
        self::assertSame('', $saved['tempmaster_passkey'] ?? null);
        self::assertSame('', $saved['tempmaster_passhash'] ?? null);
        self::assertSame('0', $saved['tempmaster_maxtime'] ?? null);
    }

    #[Test]
    public function testUpdateConfig()
    {
        $hash = self::$faker->sha1();

        $this->configService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(
                ...
                self::withConsecutive(['masterPwd', $hash], ['lastupdatempass', self::anything()])
            )->willReturn(true);

        $this->masterPass->updateConfig($hash);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configService = $this->createMock(ConfigService::class);
        $this->accountMasterPasswordService = $this->createMock(AccountMasterPasswordService::class);
        $this->customFieldCryptService = $this->createMock(CustomFieldCryptService::class);
        $this->repository = $this->createMock(Repository::class);

        $this->masterPass = new MasterPass(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->configService,
            $this->accountMasterPasswordService,
            $this->customFieldCryptService,
            $this->repository,
            $this->createStub(MasterPasswordStorageGuard::class)
        );
    }
}
