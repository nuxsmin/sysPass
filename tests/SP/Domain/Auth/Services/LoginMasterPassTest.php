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

namespace SP\Tests\Domain\Auth\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Services\Login;
use SP\Domain\Auth\Services\LoginMasterPass;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Context\ContextException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Adapters\HashServiceAdapter;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;
use SP\Domain\Crypt\Ports\TemporaryMasterPassService;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Dtos\UserMasterPassDto;
use SP\Domain\User\Models\UserMasterPassStatus;
use SP\Domain\User\Ports\UserMasterPassService;
use SP\Domain\User\Ports\UserRepository;
use SP\Domain\User\Services\UserMasterPass;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class LoginMasterPassTest
 */
#[Group('unitary')]
class LoginMasterPassTest extends UnitaryTestCase
{

    private TrackService|MockObject          $trackService;
    private RequestService|MockObject        $request;
    private MockObject|UserMasterPassService $userMasterPassService;
    private MockObject|TemporaryMasterPassService $temporaryMasterPassService;
    private LoginMasterPass                       $loginMasterPass;

    public static function wrongStatusDataProvider(): array
    {
        return [
            [UserMasterPassStatus::NotSet],
            [UserMasterPassStatus::Changed],
            [UserMasterPassStatus::Invalid],
        ];
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[Test]
    public function testLoadMasterPass()
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('');

        $userMasterPassDto = new UserMasterPassDto(UserMasterPassStatus::Ok);

        $this->userMasterPassService
            ->expects($this->once())
            ->method('load')
            ->with($userLoginDto, $userDto)
            ->willReturn($userMasterPassDto);

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[DataProvider('wrongStatusDataProvider')]
    #[Test]
    public function testLoadMasterPassWithWrongPassword(UserMasterPassStatus $userMasterPassStatus)
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('');

        $userMasterPassDto = new UserMasterPassDto($userMasterPassStatus);

        $this->userMasterPassService
            ->expects($this->once())
            ->method('load')
            ->with($userLoginDto, $userDto)
            ->willReturn($userMasterPassDto);

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('The Master Password either is not saved or is wrong');

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[Test]
    public function testLoadMasterPassWithPreviousNeeded()
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('');

        $userMasterPassDto = new UserMasterPassDto(UserMasterPassStatus::CheckOld);

        $this->userMasterPassService
            ->expects($this->once())
            ->method('load')
            ->with($userLoginDto, $userDto)
            ->willReturn($userMasterPassDto);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Your previous password is needed');

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[Test]
    public function testLoadMasterPassWithTemporary()
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('a_key', '');

        $this->temporaryMasterPassService
            ->expects($this->once())
            ->method('checkKey')
            ->with('a_key')
            ->willReturn(true);

        $this->temporaryMasterPassService
            ->expects($this->once())
            ->method('getUsingKey')
            ->with('a_key')
            ->willReturn('a_master_pass');

        $userMasterPassDto = new UserMasterPassDto(UserMasterPassStatus::Ok);

        $this->userMasterPassService
            ->expects($this->once())
            ->method('updateOnLogin')
            ->with('a_master_pass', $userLoginDto, $userDto->id)
            ->willReturn($userMasterPassDto);

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }

    public static function missingMasterHashProvider(): array
    {
        return ['null' => [false], 'absent' => [true]];
    }

    #[Test]
    #[DataProvider('missingMasterHashProvider')]
    public function temporaryLoginFailsClosedWhenSystemHashIsMissing(bool $absent): void
    {
        $user = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $login = new UserLoginDto('synthetic user', 'synthetic login password');
        $this->request->method('analyzeEncrypted')->willReturn('temporary key', '');
        $this->temporaryMasterPassService->method('checkKey')->with('temporary key')->willReturn(true);
        $this->temporaryMasterPassService->method('getUsingKey')->with('temporary key')
            ->willReturn('synthetic master');

        $config = $this->createMock(ConfigService::class);
        $lookup = $config->expects(self::once())->method('getByParam')->with('masterPwd');
        if ($absent) {
            $lookup->willThrowException(NoSuchItemException::error('missing'));
        } else {
            $lookup->willReturn(null);
        }
        $config->expects(self::never())->method('save');
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::never())->method('updateMasterPassById');
        $crypt = $this->createMock(CryptInterface::class);
        $crypt->expects(self::never())->method('encrypt');
        $store = $this->createMock(MasterKeyStore::class);
        $store->expects(self::never())->method('set');
        $userMasterPass = new UserMasterPass(
            $this->config, $this->eventDispatcherStub, $this->context, $users, $config, $crypt, $store,
            new HashServiceAdapter(),
            $this->createStub(MasterPasswordRequestLock::class)
        );
        $boundary = new LoginMasterPass(
            $this->config, $this->eventDispatcherStub, $this->context, $this->trackService,
            $this->request, $userMasterPass, $this->temporaryMasterPassService
        );

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('documented recovery procedure');
        $boundary->loadMasterPass($login, $user);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[Test]
    public function testLoadMasterPassWithTemporaryAndWrongKey()
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('a_key', '');

        $this->temporaryMasterPassService
            ->expects($this->once())
            ->method('checkKey')
            ->with('a_key')
            ->willReturn(false);

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Wrong master password');

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[DataProvider('wrongStatusDataProvider')]
    #[Test]
    public function testLoadMasterPassWithTemporaryAndInvalidStatus(UserMasterPassStatus $userMasterPassStatus)
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('a_key', '');

        $this->temporaryMasterPassService
            ->expects($this->once())
            ->method('checkKey')
            ->with('a_key')
            ->willReturn(true);

        $this->temporaryMasterPassService
            ->expects($this->once())
            ->method('getUsingKey')
            ->with('a_key')
            ->willReturn('a_master_pass');

        $userMasterPassDto = new UserMasterPassDto($userMasterPassStatus);

        $this->userMasterPassService
            ->expects($this->once())
            ->method('updateOnLogin')
            ->with('a_master_pass', $userLoginDto, $userDto->id)
            ->willReturn($userMasterPassDto);

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Wrong master password');

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[Test]
    public function testLoadMasterPassWithTemporaryAndException()
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('a_key', '');

        $this->temporaryMasterPassService
            ->expects($this->once())
            ->method('checkKey')
            ->with('a_key')
            ->willReturn(true);

        $this->temporaryMasterPassService
            ->expects($this->once())
            ->method('getUsingKey')
            ->with('a_key')
            ->willThrowException(CryptException::error('test'));

        $this->userMasterPassService
            ->expects($this->never())
            ->method('updateOnLogin');

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[Test]
    public function testLoadMasterPassWithOld()
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('', 'an_old_pass');

        $userMasterPassDto = new UserMasterPassDto(UserMasterPassStatus::Ok);

        $this->userMasterPassService
            ->expects($this->once())
            ->method('updateFromOldPass')
            ->with('an_old_pass', $userLoginDto, $userDto)
            ->willReturn($userMasterPassDto);

        $this->trackService
            ->expects($this->never())
            ->method('add');

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     * @throws SPException
     */
    #[DataProvider('wrongStatusDataProvider')]
    #[Test]
    public function testLoadMasterPassWithOldAndWrongStatus(UserMasterPassStatus $userMasterPassStatus)
    {
        $userDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData());
        $userLoginDto = new UserLoginDto('a_user', 'a_password');

        $this->request
            ->expects($this->exactly(2))
            ->method('analyzeEncrypted')
            ->with(...self::withConsecutive(['mpass'], ['oldpass']))
            ->willReturn('', 'an_old_pass');

        $userMasterPassDto = new UserMasterPassDto($userMasterPassStatus);

        $this->userMasterPassService
            ->expects($this->once())
            ->method('updateFromOldPass')
            ->with('an_old_pass', $userLoginDto, $userDto)
            ->willReturn($userMasterPassDto);

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Wrong master password');

        $this->loginMasterPass->loadMasterPass($userLoginDto, $userDto);
    }


    /**
     * @throws Exception
     * @throws ContextException
     * @throws InvalidArgumentException
     * @throws SPException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trackService = $this->createMock(TrackService::class);
        $this->trackService
            ->expects($this->atLeast(1))
            ->method('buildTrackRequest')
            ->with(Login::class)
            ->willReturn(
                new TrackRequest(
                    self::$faker->unixTime(),
                    self::$faker->colorName(),
                    self::$faker->ipv4(),
                    self::$faker->randomNumber(2)
                )
            );

        $this->request = $this->createMock(RequestService::class);
        $this->userMasterPassService = $this->createMock(UserMasterPassService::class);
        $this->temporaryMasterPassService = $this->createMock(TemporaryMasterPassService::class);

        $this->loginMasterPass = new LoginMasterPass(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->trackService,
            $this->request,
            $this->userMasterPassService,
            $this->temporaryMasterPassService
        );
    }
}
