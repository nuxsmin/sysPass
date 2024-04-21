<?php
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

namespace SPT\Domain\User\Services;

use JsonException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Crypt\Hash;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Dtos\UserLoginRequest;
use SP\Domain\User\Dtos\UserMasterPassDto;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Ports\UserMasterPassService;
use SP\Domain\User\Ports\UserRepository;
use SP\Domain\User\Services\User;
use SP\Domain\User\Services\UserMasterPassStatus;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SPT\Generators\UserDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class UserTest
 */
#[Group('unitary')]
class UserTest extends UnitaryTestCase
{

    private MockObject|UserRepository        $userRepository;
    private MockObject|UserMasterPassService $userMasterPassService;
    private User                             $user;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $itemSearchData = new ItemSearchDto(
            self::$faker->text(),
            self::$faker->randomNumber(2),
            self::$faker->randomNumber(2)
        );

        $queryResult = new QueryResult([$user]);

        $this->userRepository
            ->expects($this->once())
            ->method('search')
            ->with($itemSearchData)
            ->willReturn($queryResult);

        $this->assertEquals($queryResult, $this->user->search($itemSearchData));
    }

    /**
     * @throws SPException
     */
    public function testGetById()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $queryResult = new QueryResult([$user]);

        $this->userRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn($queryResult);

        $this->assertEquals($user, $this->user->getById(100));
    }

    /**
     * @throws SPException
     */
    public function testGetByIdWithNoItems()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn(new QueryResult());

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('User does not exist');

        $this->user->getById(100);
    }


    /**
     * @throws ConstraintException
     * @throws JsonException
     * @throws QueryException
     */
    public function testUpdatePreferencesById()
    {
        $userPreferences = UserDataGenerator::factory()->buildUserPreferencesData();

        $this->userRepository
            ->expects($this->once())
            ->method('updatePreferencesById')
            ->with(100, $userPreferences)
            ->willReturn(10);

        $out = $this->user->updatePreferencesById(100, $userPreferences);

        $this->assertEquals(10, $out);
    }

    public function testGetAll()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $queryResult = new QueryResult([$user]);

        $this->userRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($queryResult);

        $this->assertEquals([$user], $this->user->getAll());
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 3));

        $this->assertEquals(3, $this->user->deleteByIdBatch([100, 200, 300]));
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithException()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the users');

        $this->user->deleteByIdBatch([100, 200, 300]);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testCreateWithMasterPass()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $userMasterPassDto = new UserMasterPassDto(
            UserMasterPassStatus::Ok,
            'a_master_pass',
            'a_crypt_master_pass',
            'a_secured_key'
        );

        $this->userMasterPassService
            ->expects($this->once())
            ->method('create')
            ->with('a_master_pass', $user->getLogin(), 'a_password')
            ->willReturn($userMasterPassDto);

        $this->userRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(static function (UserModel $targetUser) use ($user) {
                    $targetUserProps = $targetUser->toArray(null, ['mPass', 'mKey', 'pass', 'lastUpdateMPass']);
                    $userProps = $user->toArray(null, ['mPass', 'mKey', 'pass', 'lastUpdateMPass']);

                    return $targetUser->getMPass() === 'a_crypt_master_pass'
                           && $targetUser->getMKey() === 'a_secured_key'
                           && Hash::checkHashKey('a_password', $targetUser->getPass())
                           && $targetUserProps === $userProps;
                })
            )
            ->willReturn(new QueryResult(null, 0, 10));

        $this->user->createWithMasterPass($user, 'a_password', 'a_master_pass');
    }

    public function testGetUserEmailForAll()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $this->userRepository
            ->expects($this->once())
            ->method('getUserEmail')
            ->willReturn(new QueryResult([$user]));

        $this->assertEquals([$user], $this->user->getUserEmailForAll());
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testUpdatePass()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('updatePassById')
            ->with(
                self::callback(static function (UserModel $user) {
                    return $user->getId() === 100
                           && Hash::checkHashKey('a_password', $user->getPass())
                           && $user->isChangedPass() === true
                           && $user->isChangePass() === false;
                })
            )
            ->willReturn(1);

        $this->user->updatePass(100, 'a_password');
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testUpdatePassWithException()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('updatePassById')
            ->willReturn(0);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the password');

        $this->user->updatePass(100, 'a_password');
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUserEmailForGroup()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $this->userRepository
            ->expects($this->once())
            ->method('getUserEmailForGroup')
            ->with(100)
            ->willReturn(new QueryResult([$user]));

        $this->assertEquals([$user], $this->user->getUserEmailForGroup(100));
    }

    /**
     * @throws FileException
     * @throws SPException
     */
    public function testCreateOnLoginWithLdap()
    {
        $userLoginRequest = new UserLoginRequest(
            self::$faker->userName(),
            self::$faker->password(),
            self::$faker->name(),
            self::$faker->email(),
            true
        );

        $configData = $this->config->getConfigData();
        $configData->setLdapDefaultGroup(100);
        $configData->setLdapDefaultProfile(200);

        $this->config->save($configData);

        $this->userRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(static function (UserModel $user) use ($userLoginRequest) {
                    return $user->getLogin() === $userLoginRequest->getLogin()
                           && $user->getName() === $userLoginRequest->getName()
                           && Hash::checkHashKey($userLoginRequest->getPassword(), $user->getPass())
                           && $user->getEmail() === $userLoginRequest->getEmail()
                           && $user->isLdap() === $userLoginRequest->getisLdap()
                           && $user->getUserGroupId() === 100
                           && $user->getUserProfileId() === 200;
                })
            );

        $this->user->createOnLogin($userLoginRequest);
    }

    /**
     * @throws FileException
     * @throws SPException
     */
    public function testCreateOnLoginWithNoLdap()
    {
        $userLoginRequest = new UserLoginRequest(
            self::$faker->userName(),
            self::$faker->password(),
            self::$faker->name(),
            self::$faker->email(),
            false
        );

        $configData = $this->config->getConfigData();
        $configData->setSsoDefaultGroup(101);
        $configData->setSsoDefaultProfile(201);

        $this->config->save($configData);

        $this->userRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(static function (UserModel $user) use ($userLoginRequest) {
                    return $user->getLogin() === $userLoginRequest->getLogin()
                           && $user->getName() === $userLoginRequest->getName()
                           && Hash::checkHashKey($userLoginRequest->getPassword(), $user->getPass())
                           && $user->getEmail() === $userLoginRequest->getEmail()
                           && $user->isLdap() === $userLoginRequest->getisLdap()
                           && $user->getUserGroupId() === 101
                           && $user->getUserProfileId() === 201;
                })
            );

        $this->user->createOnLogin($userLoginRequest);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetByLogin()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $this->userRepository
            ->expects($this->once())
            ->method('getByLogin')
            ->with('a_login')
            ->willReturn(new QueryResult([$user]));

        $this->assertEquals($user, $this->user->getByLogin('a_login'));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetByLoginWithException()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $this->userRepository
            ->expects($this->once())
            ->method('getByLogin')
            ->with('a_login')
            ->willReturn(new QueryResult());

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('User not found');

        $this->user->getByLogin('a_login');
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testUpdateLastLoginById()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('updateLastLoginById')
            ->with(100)
            ->willReturn(10);

        $this->user->updateLastLoginById(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testUpdateLastLoginByIdWithException()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('updateLastLoginById')
            ->with(100)
            ->willReturn(0);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('User not found');

        $this->user->updateLastLoginById(100);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckExistsByLogin()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('checkExistsByLogin')
            ->with('a_login')
            ->willReturn(true);

        $this->assertTrue($this->user->checkExistsByLogin('a_login'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckExistsByLoginWithFalse()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('checkExistsByLogin')
            ->with('a_login')
            ->willReturn(false);

        $this->assertFalse($this->user->checkExistsByLogin('a_login'));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult(null, 1));

        $this->user->delete(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteWithNoResult()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult());

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('User not found');

        $this->user->delete(100);
    }

    /**
     * @throws SPException
     */
    public function testCreate()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $this->userRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(static function (UserModel $input) use ($user) {
                    return $input->toArray(null, ['pass']) === $user->toArray(null, ['pass'])
                           && Hash::checkHashKey($user->getPass(), $input->getPass());
                })
            )
            ->willReturn(new QueryResult(null, 0, 10));

        $this->assertEquals(10, $this->user->create($user));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateOnLoginWithLdap()
    {
        $userLoginRequest = new UserLoginRequest(
            self::$faker->userName(),
            self::$faker->password(),
            self::$faker->name(),
            self::$faker->email(),
            true
        );

        $this->userRepository
            ->expects($this->once())
            ->method('updateOnLogin')
            ->with(
                self::callback(static function (UserModel $user) use ($userLoginRequest) {
                    return $user->getLogin() === $userLoginRequest->getLogin()
                           && $user->getName() === $userLoginRequest->getName()
                           && Hash::checkHashKey($userLoginRequest->getPassword(), $user->getPass())
                           && $user->getEmail() === $userLoginRequest->getEmail()
                           && $user->isLdap() === $userLoginRequest->getisLdap();
                })
            );

        $this->user->updateOnLogin($userLoginRequest);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdateOnLoginWithNoLdap()
    {
        $userLoginRequest = new UserLoginRequest(
            self::$faker->userName(),
            self::$faker->password(),
            self::$faker->name(),
            self::$faker->email(),
            false
        );

        $this->userRepository
            ->expects($this->once())
            ->method('updateOnLogin')
            ->with(
                self::callback(static function (UserModel $user) use ($userLoginRequest) {
                    return $user->getLogin() === $userLoginRequest->getLogin()
                           && $user->getName() === $userLoginRequest->getName()
                           && Hash::checkHashKey($userLoginRequest->getPassword(), $user->getPass())
                           && $user->getEmail() === $userLoginRequest->getEmail()
                           && $user->isLdap() === $userLoginRequest->getisLdap();
                })
            );

        $this->user->updateOnLogin($userLoginRequest);
    }

    public function testGetUserEmailById()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $this->userRepository
            ->expects($this->once())
            ->method('getUserEmailById')
            ->with([100, 200])
            ->willReturn(new QueryResult([$user]));

        $out = $this->user->getUserEmailById([100, 200]);

        $this->assertEquals([$user], $out);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($user)
            ->willReturn(10);

        $this->user->update($user);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdateWithException()
    {
        $user = UserDataGenerator::factory()->buildUserData();

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($user)
            ->willReturn(0);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the user');

        $this->user->update($user);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUsageForUser()
    {
        $this->userRepository
            ->expects($this->once())
            ->method('getUsageForUser')
            ->with(100)
            ->willReturn(new QueryResult([1]));

        $out = $this->user->getUsageForUser(100);

        $this->assertEquals([1], $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userMasterPassService = $this->createMock(UserMasterPassService::class);

        $this->user = new User($this->application, $this->userRepository, $this->userMasterPassService);
    }
}
