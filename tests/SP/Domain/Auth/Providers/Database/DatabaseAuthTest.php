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

namespace SP\Tests\Domain\Auth\Providers\Database;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Crypt\Hash;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Providers\Database\DatabaseAuth;
use SP\Domain\User\Ports\UserPassService;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class DatabaseAuthTest
 *
 */
#[Group('unitary')]
class DatabaseAuthTest extends UnitaryTestCase
{

    private UserService|MockObject     $userService;
    private MockObject|UserPassService $userPassService;
    private DatabaseAuth                    $databaseAuth;

    public function testAuthenticate()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;
        $hashedPass = Hash::hashKey($pass);

        $userData = UserDataGenerator::factory()->buildUserData()->mutate(['login' => $user, 'pass' => $hashedPass]);

        $this->userService
            ->expects(self::once())
            ->method('getByLogin')
            ->with($user)
            ->willReturn($userData);

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);
        $userLoginData->setLoginPass($pass);

        self::assertTrue($this->databaseAuth->authenticate($userLoginData)->isOk());
    }

    public function testAuthenticateWithWrongLogin()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $this->userService
            ->expects(self::once())
            ->method('getByLogin')
            ->with($user)
            ->willThrowException(new NoSuchItemException('User does not exist'));

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);
        $userLoginData->setLoginPass($pass);

        self::assertFalse($this->databaseAuth->authenticate($userLoginData)->isOk());
    }

    public function testAuthenticateWithWrongPass()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $userData = UserDataGenerator::factory()->buildUserData()->mutate(['login' => $user]);

        $this->userService
            ->expects(self::once())
            ->method('getByLogin')
            ->with($user)
            ->willReturn($userData);

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);
        $userLoginData->setLoginPass($pass);

        self::assertFalse($this->databaseAuth->authenticate($userLoginData)->isOk());
    }

    public function testAuthenticateWithMigrationBySHA1()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;

        $userData = UserDataGenerator::factory()
                                     ->buildUserData()
                                     ->mutate(
                                         [
                                             'login' => $user,
                                             'pass' => md5($pass),
                                             'isMigrate' => true
                                         ]
                                     );

        $this->userService
            ->expects(self::once())
            ->method('getByLogin')
            ->with($user)
            ->willReturn($userData);

        $this->userPassService
            ->expects(self::once())
            ->method('migrateUserPassById')
            ->with($userData->getId(), $pass);

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);
        $userLoginData->setLoginPass($pass);

        self::assertTrue($this->databaseAuth->authenticate($userLoginData)->isOk());
    }

    public function testAuthenticateWithMigrationByMD5()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;
        $salt = self::$faker->password;

        $userData = UserDataGenerator::factory()
                                     ->buildUserData()
                                     ->mutate(
                                         [
                                             'login' => $user,
                                             'pass' => sha1($salt . $pass),
                                             'hashSalt' => $salt,
                                             'isMigrate' => true
                                         ]
                                     );

        $this->userService
            ->expects(self::once())
            ->method('getByLogin')
            ->with($user)
            ->willReturn($userData);

        $this->userPassService
            ->expects(self::once())
            ->method('migrateUserPassById')
            ->with($userData->getId(), $pass);

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);
        $userLoginData->setLoginPass($pass);

        self::assertTrue($this->databaseAuth->authenticate($userLoginData)->isOk());
    }

    public function testAuthenticateWithMigrationByCrypt()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;
        $salt = self::$faker->password;

        $userData = UserDataGenerator::factory()
                                     ->buildUserData()
                                     ->mutate(
                                         [
                                             'login' => $user,
                                             'pass' => crypt($pass, $salt),
                                             'hashSalt' => $salt,
                                             'isMigrate' => true
                                         ]
                                     );

        $this->userService
            ->expects(self::once())
            ->method('getByLogin')
            ->with($user)
            ->willReturn($userData);

        $this->userPassService
            ->expects(self::once())
            ->method('migrateUserPassById')
            ->with($userData->getId(), $pass);

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);
        $userLoginData->setLoginPass($pass);

        self::assertTrue($this->databaseAuth->authenticate($userLoginData)->isOk());
    }

    public function testAuthenticateWithMigrationByHash()
    {
        $user = self::$faker->userName;
        $pass = self::$faker->password;
        $hashedPass = Hash::hashKey($pass);

        $userData = UserDataGenerator::factory()
                                     ->buildUserData()
                                     ->mutate(
                                         [
                                             'login' => $user,
                                             'pass' => $hashedPass,
                                             'isMigrate' => true
                                         ]
                                     );

        $this->userService
            ->expects(self::once())
            ->method('getByLogin')
            ->with($user)
            ->willReturn($userData);

        $this->userPassService
            ->expects(self::once())
            ->method('migrateUserPassById')
            ->with($userData->getId(), $pass);

        $userLoginData = new UserLoginDto();
        $userLoginData->setLoginUser($user);
        $userLoginData->setLoginPass($pass);

        self::assertTrue($this->databaseAuth->authenticate($userLoginData)->isOk());
    }

    public function testIsAuthGranted()
    {
        self::assertTrue($this->databaseAuth->isAuthGranted());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = $this->createMock(UserService::class);
        $this->userPassService = $this->createMock(UserPassService::class);

        $this->databaseAuth = new DatabaseAuth($this->userService, $this->userPassService);
    }

}
