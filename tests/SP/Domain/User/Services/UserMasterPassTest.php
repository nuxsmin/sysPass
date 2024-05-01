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

namespace SP\Tests\Domain\User\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Crypt\Hash;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserRepository;
use SP\Domain\User\Services\UserMasterPass;
use SP\Domain\User\Services\UserMasterPassStatus;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class UserMasterPassTest
 */
#[Group('unitary')]
class UserMasterPassTest extends UnitaryTestCase
{

    private MockObject|UserRepository $userRepository;
    private MockObject|ConfigService  $configService;
    private MockObject|CryptInterface $crypt;
    private UserMasterPass            $userMasterPass;

    /**
     * @throws ServiceException
     */
    public function testLoad()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5');

        $key = $userLoginDto->getLoginPass() .
               $userLoginDto->getLoginUser() .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->with($user->getMPass(), $user->getMKey(), $key)
            ->willReturn('a_master_pass');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::Ok, $out->getUserMasterPassStatus());
        $this->assertEquals('a_master_pass', $out->getClearMasterPass());
        $this->assertEquals($userDataDto->getMPass(), $out->getCryptMasterPass());
        $this->assertEquals($userDataDto->getMKey(), $out->getCryptSecuredKey());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithUserPass()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5');

        $key = 'a_password' .
               $userLoginDto->getLoginUser() .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->with($user->getMPass(), $user->getMKey(), $key)
            ->willReturn('a_master_pass');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto, 'a_password');

        $this->assertEquals(UserMasterPassStatus::Ok, $out->getUserMasterPassStatus());
        $this->assertEquals('a_master_pass', $out->getClearMasterPass());
        $this->assertEquals($userDataDto->getMPass(), $out->getCryptMasterPass());
        $this->assertEquals($userDataDto->getMKey(), $out->getCryptSecuredKey());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithNotSet()
    {
        $userDataDto = new UserDataDto(new User());
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->never())
            ->method('getByParam');

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::NotSet, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithNotSetAndEmptyPass()
    {
        $userDataDto = new UserDataDto(new User(['use' => self::$faker->userName]));
        $userLoginDto = new UserLoginDto(self::$faker->userName());

        $this->configService
            ->expects($this->never())
            ->method('getByParam');

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::NotSet, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithNotSetAndEmptyUser()
    {
        $userDataDto = new UserDataDto(new User(['pass' => self::$faker->password]));
        $userLoginDto = new UserLoginDto();

        $this->configService
            ->expects($this->never())
            ->method('getByParam');

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::NotSet, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithNotSetAndNullHash()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->once())
            ->method('getByParam')
            ->willReturn(null);

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::NotSet, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithChanged()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 0]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5');

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::Changed, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithCheckOld()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => true, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), null);

        $this->configService
            ->expects($this->exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5');

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::CheckOld, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithCryptException()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5');

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->willThrowException(CryptException::error('test'));

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::CheckOld, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithInvalid()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5');

        $key = $userLoginDto->getLoginPass() .
               $userLoginDto->getLoginUser() .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->with($user->getMPass(), $user->getMKey(), $key)
            ->willReturn('a_pass');

        $out = $this->userMasterPass->load($userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::Invalid, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testLoadWithException()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5');

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->userMasterPass->load($userLoginDto, $userDataDto);
    }


    /**
     * @throws ServiceException
     */
    public function testUpdateFromOldPass()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->exactly(3))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass'], ['masterPwd']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5', Hash::hashKey('a_master_pass'));

        $oldKey = 'an_old_user_pass' .
                  $userLoginDto->getLoginUser() .
                  $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->with($user->getMPass(), $user->getMKey(), $oldKey)
            ->willReturn('a_master_pass');

        $key = $userLoginDto->getLoginPass() .
               $userLoginDto->getLoginUser() .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('makeSecuredKey')
            ->with($key)
            ->willReturn('a_secure_key');

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with('a_master_pass', 'a_secure_key', $key)
            ->willReturn('encrypted');

        $this->userRepository
            ->expects($this->once())
            ->method('updateMasterPassById')
            ->with($userDataDto->getId(), 'encrypted', 'a_secure_key');

        $out = $this->userMasterPass->updateFromOldPass('an_old_user_pass', $userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::Ok, $out->getUserMasterPassStatus());
        $this->assertEquals('encrypted', $out->getCryptMasterPass());
        $this->assertEquals('a_secure_key', $out->getCryptSecuredKey());
        $this->assertEquals('a_master_pass', $out->getClearMasterPass());
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateFromOldPassWithInvalid()
    {
        $user = UserDataGenerator::factory()
                                 ->buildUserData()
                                 ->mutate(['isChangedPass' => false, 'lastUpdateMPass' => 10]);

        $userDataDto = new UserDataDto($user);
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->exactly(2))
            ->method('getByParam')
            ->with(...self::withConsecutive(['masterPwd'], ['lastupdatempass']))
            ->willReturn(Hash::hashKey('a_master_pass'), '5');

        $oldKey = 'an_old_user_pass' .
                  $userLoginDto->getLoginUser() .
                  $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->with($user->getMPass(), $user->getMKey(), $oldKey)
            ->willReturn('another_master_pass');

        $this->crypt
            ->expects($this->never())
            ->method('makeSecuredKey');

        $this->crypt
            ->expects($this->never())
            ->method('encrypt');

        $this->userRepository
            ->expects($this->never())
            ->method('updateMasterPassById');

        $out = $this->userMasterPass->updateFromOldPass('an_old_user_pass', $userLoginDto, $userDataDto);

        $this->assertEquals(UserMasterPassStatus::Invalid, $out->getUserMasterPassStatus());
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateOnLogin()
    {
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->once())
            ->method('getByParam')
            ->with('masterPwd')
            ->willReturn(Hash::hashKey('a_master_pass'));

        $key = $userLoginDto->getLoginPass() .
               $userLoginDto->getLoginUser() .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('makeSecuredKey')
            ->with($key)
            ->willReturn('a_secure_key');

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with('a_master_pass', 'a_secure_key', $key)
            ->willReturn('encrypted');

        $this->userRepository
            ->expects($this->once())
            ->method('updateMasterPassById')
            ->with(100, 'encrypted', 'a_secure_key');

        $out = $this->userMasterPass->updateOnLogin('a_master_pass', $userLoginDto, 100);

        $this->assertEquals(UserMasterPassStatus::Ok, $out->getUserMasterPassStatus());
        $this->assertEquals('encrypted', $out->getCryptMasterPass());
        $this->assertEquals('a_secure_key', $out->getCryptSecuredKey());
        $this->assertEquals('a_master_pass', $out->getClearMasterPass());
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateOnLoginWithSaveHash()
    {
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->once())
            ->method('getByParam')
            ->with('masterPwd')
            ->willReturn(null);

        $this->configService
            ->expects($this->once())
            ->method('save')
            ->with(
                'masterPwd',
                self::callback(static function (string $hash) {
                    return Hash::checkHashKey('a_master_pass', $hash);
                })
            );

        $key = $userLoginDto->getLoginPass() .
               $userLoginDto->getLoginUser() .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('makeSecuredKey')
            ->with($key)
            ->willReturn('a_secure_key');

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with('a_master_pass', 'a_secure_key', $key)
            ->willReturn('encrypted');

        $this->userRepository
            ->expects($this->once())
            ->method('updateMasterPassById')
            ->with(100, 'encrypted', 'a_secure_key');

        $out = $this->userMasterPass->updateOnLogin('a_master_pass', $userLoginDto, 100);

        $this->assertEquals(UserMasterPassStatus::Ok, $out->getUserMasterPassStatus());
        $this->assertEquals('encrypted', $out->getCryptMasterPass());
        $this->assertEquals('a_secure_key', $out->getCryptSecuredKey());
        $this->assertEquals('a_master_pass', $out->getClearMasterPass());
    }

    /**
     * @throws ServiceException
     */
    public function testUpdateOnLoginWithException()
    {
        $userLoginDto = new UserLoginDto(self::$faker->userName(), self::$faker->password());

        $this->configService
            ->expects($this->once())
            ->method('getByParam')
            ->with('masterPwd')
            ->willReturn(null);

        $this->configService
            ->expects($this->once())
            ->method('save')
            ->with(
                'masterPwd',
                self::callback(static function (string $hash) {
                    return Hash::checkHashKey('a_master_pass', $hash);
                })
            );

        $key = $userLoginDto->getLoginPass() .
               $userLoginDto->getLoginUser() .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('makeSecuredKey')
            ->with($key)
            ->willReturn('a_secure_key');

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with('a_master_pass', 'a_secure_key', $key)
            ->willReturn('encrypted');

        $this->userRepository
            ->expects($this->once())
            ->method('updateMasterPassById')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->userMasterPass->updateOnLogin('a_master_pass', $userLoginDto, 100);
    }

    /**
     * @throws ServiceException
     */
    public function testCreate()
    {
        $key = 'a_password' .
               'a_login' .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('makeSecuredKey')
            ->with($key)
            ->willReturn('a_secure_key');

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with('a_master_pass', 'a_secure_key', $key)
            ->willReturn('encrypted');

        $out = $this->userMasterPass->create('a_master_pass', 'a_login', 'a_password');

        $this->assertEquals(UserMasterPassStatus::Ok, $out->getUserMasterPassStatus());
        $this->assertEquals('encrypted', $out->getCryptMasterPass());
        $this->assertEquals('a_secure_key', $out->getCryptSecuredKey());
        $this->assertEquals('a_master_pass', $out->getClearMasterPass());
    }

    /**
     * @throws ServiceException
     */
    public function testCreateWithLongKey()
    {
        $key = 'a_password' .
               'a_login' .
               $this->config->getConfigData()->getPasswordSalt();

        $longKey = str_repeat('a', 1001);

        $this->crypt
            ->expects($this->once())
            ->method('makeSecuredKey')
            ->with($key)
            ->willReturn($longKey);

        $this->crypt
            ->expects($this->never())
            ->method('encrypt');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->userMasterPass->create('a_master_pass', 'a_login', 'a_password');
    }

    /**
     * @throws ServiceException
     */
    public function testCreateWithLongMasterPass()
    {
        $key = 'a_password' .
               'a_login' .
               $this->config->getConfigData()->getPasswordSalt();


        $this->crypt
            ->expects($this->once())
            ->method('makeSecuredKey')
            ->with($key)
            ->willReturn('a_secured_key');

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with('a_master_pass', 'a_secured_key', $key)
            ->willReturn(str_repeat('a', 1001));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->userMasterPass->create('a_master_pass', 'a_login', 'a_password');
    }

    /**
     * @throws ServiceException
     */
    public function testCreateWithException()
    {
        $key = 'a_password' .
               'a_login' .
               $this->config->getConfigData()->getPasswordSalt();

        $this->crypt
            ->expects($this->once())
            ->method('makeSecuredKey')
            ->with($key)
            ->willReturn('a_secured_key');

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with('a_master_pass', 'a_secured_key', $key)
            ->willThrowException(CryptException::error('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->userMasterPass->create('a_master_pass', 'a_login', 'a_password');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepository::class);
        $this->configService = $this->createMock(ConfigService::class);
        $this->crypt = $this->createMock(CryptInterface::class);

        $this->userMasterPass = new UserMasterPass(
            $this->application,
            $this->userRepository,
            $this->configService,
            $this->crypt
        );
    }
}
