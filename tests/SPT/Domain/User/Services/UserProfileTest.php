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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserProfileRepository;
use SP\Domain\User\Services\UserProfile;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\UserProfileDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class UserProfileTest
 */
#[Group('unitary')]
class UserProfileTest extends UnitaryTestCase
{

    private MockObject|UserProfileRepository $userProfileRepository;
    private UserProfile                      $userProfile;

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetById()
    {
        $userProfile = UserProfileDataGenerator::factory()->buildUserProfileData();

        $this->userProfileRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn(new QueryResult([$userProfile]));

        $out = $this->userProfile->getById(100);

        $this->assertEquals($userProfile, $out);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetByIdWithNoItems()
    {
        $userProfile = UserProfileDataGenerator::factory()->buildUserProfileData();

        $this->userProfileRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Profile not found');

        $this->userProfile->getById(100);
    }

    /**
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $userProfile = UserProfileDataGenerator::factory()->buildUserProfileData();

        $this->userProfileRepository
            ->expects($this->once())
            ->method('create')
            ->with($userProfile)
            ->willReturn(new QueryResult(null, 0, 100));

        $this->assertEquals(100, $this->userProfile->create($userProfile));
    }

    public function testGetAll()
    {
        $userProfile = UserProfileDataGenerator::factory()->buildUserProfileData();

        $this->userProfileRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn(new QueryResult([$userProfile]));

        $out = $this->userProfile->getAll();

        $this->assertEquals([$userProfile], $out);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $userProfile = UserProfileDataGenerator::factory()->buildUserProfileData();

        $this->userProfileRepository
            ->expects($this->once())
            ->method('update')
            ->with($userProfile)
            ->willReturn(10);

        $this->userProfile->update($userProfile);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function testUpdateWithException()
    {
        $userProfile = UserProfileDataGenerator::factory()->buildUserProfileData();

        $this->userProfileRepository
            ->expects($this->once())
            ->method('update')
            ->with($userProfile)
            ->willReturn(0);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the profile');

        $this->userProfile->update($userProfile);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->userProfileRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 3));

        $this->userProfile->deleteByIdBatch([100, 200, 300]);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithException()
    {
        $this->userProfileRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 1));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while removing the profiles');

        $this->userProfile->deleteByIdBatch([100, 200, 300]);
    }

    public function testSearch()
    {
        $itemSearchData = new ItemSearchDto(
            self::$faker->userName(),
            self::$faker->randomNumber(2),
            self::$faker->randomNumber(2)
        );

        $queryResult = new QueryResult([1]);

        $this->userProfileRepository
            ->expects($this->once())
            ->method('search')
            ->with($itemSearchData)
            ->willReturn($queryResult);

        $out = $this->userProfile->search($itemSearchData);

        self::assertEquals($queryResult, $out);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->userProfileRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult(null, 1));

        $this->userProfile->delete(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteWithException()
    {
        $this->userProfileRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult());

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Profile not found');

        $this->userProfile->delete(100);
    }

    public function testGetUsersForProfile()
    {
        $userProfile = UserProfileDataGenerator::factory()->buildUserProfileData();

        $this->userProfileRepository
            ->expects($this->once())
            ->method('getAny')
            ->with(['id', 'login'], User::TABLE, 'userProfileId = :userProfileId', ['userProfileId' => 100])
            ->willReturn(new QueryResult([$userProfile]));

        $out = $this->userProfile->getUsersForProfile(100);

        $this->assertEquals([$userProfile], $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userProfileRepository = $this->createMock(UserProfileRepository::class);

        $this->userProfile = new UserProfile($this->application, $this->userProfileRepository);
    }
}
