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

namespace SP\Tests\Infrastructure\User\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\DuplicatedItemException;
use SP\Domain\User\Models\UserProfile as UserProfileModel;
use SP\Infrastructure\User\Repositories\UserProfile;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the UserProfile repository queries.
 */
#[Group('integration')]
final class UserProfileDbTest extends DatabaseRepositoryTestCase
{
    private UserProfile $userProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userProfile = new UserProfile(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function getByIdReturnsTheProfile(): void
    {
        $profile = $this->userProfile->getById(1)->getData(UserProfileModel::class);

        self::assertSame(1, $profile->getId());
        self::assertSame('Admin', $profile->getName());
        self::assertNotEmpty($profile->getProfile());

        self::assertSame(0, $this->userProfile->getById(999)->getNumRows());
    }

    #[Test]
    public function getAllReturnsEveryProfileOrderedByName(): void
    {
        self::assertSame(
            ['Admin', 'Demo', 'Usuarios'],
            array_map(
                static fn(UserProfileModel $profile) => $profile->getName(),
                $this->userProfile->getAll()->getDataAsArray(UserProfileModel::class)
            )
        );
    }

    #[Test]
    public function createPersistsTheProfile(): void
    {
        $serialized = serialize(['custom' => true]);

        $result = $this->userProfile->create(new UserProfileModel([
            'name' => 'Custom',
            'profile' => $serialized,
        ]));

        $profile = $this->userProfile->getById($result->getLastId())->getData(UserProfileModel::class);

        self::assertSame('Custom', $profile->getName());
        self::assertSame($serialized, $profile->getProfile());
    }

    #[Test]
    public function createRejectsADuplicatedNameIgnoringCase(): void
    {
        $this->expectException(DuplicatedItemException::class);

        $this->userProfile->create(new UserProfileModel([
            'name' => 'admin',
            'profile' => serialize([]),
        ]));
    }

    #[Test]
    public function updateChangesTheProfile(): void
    {
        $serialized = serialize(['updated' => true]);

        $affected = $this->userProfile->update(new UserProfileModel([
            'id' => 3,
            'name' => 'Usuarios 2',
            'profile' => $serialized,
        ]));

        self::assertSame(1, $affected);

        $profile = $this->userProfile->getById(3)->getData(UserProfileModel::class);

        self::assertSame('Usuarios 2', $profile->getName());
        self::assertSame($serialized, $profile->getProfile());
    }

    #[Test]
    public function updateRejectsANameUsedByAnotherProfileIgnoringCase(): void
    {
        $this->expectException(DuplicatedItemException::class);

        $this->userProfile->update(new UserProfileModel([
            'id' => 3,
            'name' => 'admin',
            'profile' => serialize([]),
        ]));
    }

    #[Test]
    public function searchFiltersByNameAndPaginates(): void
    {
        $result = $this->userProfile->search(new ItemSearchDto('Dem'));

        self::assertSame(1, $result->getNumRows());
        self::assertSame(2, $result->getDataAsArray(UserProfileModel::class)[0]->getId());

        $all = $this->userProfile->search(new ItemSearchDto());

        self::assertSame(3, $all->getTotalNumRows());
        self::assertSame(
            ['Admin', 'Demo', 'Usuarios'],
            array_map(
                static fn(UserProfileModel $profile) => $profile->getName(),
                $all->getDataAsArray(UserProfileModel::class)
            )
        );

        $page = $this->userProfile->search(new ItemSearchDto(null, 1, 1));

        self::assertSame(
            ['Demo'],
            array_map(
                static fn(UserProfileModel $profile) => $profile->getName(),
                $page->getDataAsArray(UserProfileModel::class)
            )
        );
    }

    #[Test]
    public function deleteByIdBatchRemovesOnlyTheSelectedProfiles(): void
    {
        $first = $this->userProfile->create(new UserProfileModel([
            'name' => 'Temporary A',
            'profile' => serialize([]),
        ]));
        $second = $this->userProfile->create(new UserProfileModel([
            'name' => 'Temporary B',
            'profile' => serialize([]),
        ]));

        $this->userProfile->deleteByIdBatch([$first->getLastId(), $second->getLastId()]);

        self::assertSame(
            ['Admin', 'Demo', 'Usuarios'],
            array_map(
                static fn(UserProfileModel $profile) => $profile->getName(),
                $this->userProfile->getAll()->getDataAsArray(UserProfileModel::class)
            )
        );

        self::assertSame(0, $this->userProfile->deleteByIdBatch([])->getAffectedNumRows());
    }

    #[Test]
    public function deleteRemovesASingleProfile(): void
    {
        $this->userProfile->delete(3);

        self::assertSame(0, $this->userProfile->getById(3)->getNumRows());
        self::assertSame(2, $this->userProfile->getAll()->getNumRows());
    }

    #[Test]
    public function deleteRejectsAProfileInUse(): void
    {
        $this->expectException(ConstraintException::class);

        $this->userProfile->delete(1);
    }
}
