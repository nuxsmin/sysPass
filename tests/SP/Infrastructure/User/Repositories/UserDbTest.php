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
use SP\Domain\User\Models\User as UserModel;
use SP\Infrastructure\User\Repositories\User;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the User repository queries.
 */
#[Group('integration')]
final class UserDbTest extends DatabaseRepositoryTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function checkExistsByLoginMatchesTheLogin(): void
    {
        self::assertTrue($this->user->checkExistsByLogin('user_a'));
        self::assertFalse($this->user->checkExistsByLogin('nobody'));
    }

    #[Test]
    public function checkExistsByLoginMatchesTheSsoLoginOfAUser(): void
    {
        $this->executeSql("UPDATE User SET ssoLogin = 'sso_only' WHERE id = 4");

        self::assertTrue($this->user->checkExistsByLogin('sso_only'));
    }

    #[Test]
    public function getByLoginReturnsTheUserAndItsGroupName(): void
    {
        $result = $this->user->getByLogin('user_a');

        self::assertSame(1, $result->getNumRows());

        $user = $result->getData(UserModel::class);

        self::assertSame(3, $user->getId());
        self::assertSame('User A', $user->getName());
        self::assertSame('Demo', $user->toArray(['userGroupName'])['userGroupName']);
    }

    #[Test]
    public function getByLoginMatchesTheSsoLoginOfAUser(): void
    {
        $this->executeSql("UPDATE User SET ssoLogin = 'sso_only' WHERE id = 4");

        $result = $this->user->getByLogin('sso_only');

        self::assertSame(1, $result->getNumRows());
        self::assertSame(4, $result->getData(UserModel::class)->getId());
    }

    #[Test]
    public function updateOnLoginUpdatesTheUserMatchedByLogin(): void
    {
        $affected = $this->user->updateOnLogin(new UserModel([
            'login' => 'user_a',
            'pass' => 'new_pass',
            'name' => 'Updated User A',
            'email' => 'updated@syspass.org',
            'isLdap' => true,
        ]));

        self::assertSame(1, $affected);

        $user = $this->user->getById(3)->getData(UserModel::class);

        self::assertSame('new_pass', $user->getPass());
        self::assertSame('Updated User A', $user->getName());
        self::assertSame('updated@syspass.org', $user->getEmail());
        self::assertSame(1, $user->getLoginCount());
    }

    #[Test]
    public function updateOnLoginUpdatesTheUserMatchedBySsoLogin(): void
    {
        $this->executeSql("UPDATE User SET ssoLogin = 'sso_only' WHERE id = 4");

        $affected = $this->user->updateOnLogin(new UserModel([
            'login' => 'sso_only',
            'pass' => 'sso_pass',
            'name' => 'SSO User B',
            'email' => 'sso@syspass.org',
            'isLdap' => true,
        ]));

        self::assertSame(1, $affected);
        self::assertSame('SSO User B', $this->user->getById(4)->getData(UserModel::class)->getName());
    }

    #[Test]
    public function updatePassByIdClearsTheHashSalt(): void
    {
        $affected = $this->user->updatePassById(new UserModel([
            'id' => 3,
            'pass' => 'new_pass',
            'isChangePass' => false,
            'isChangedPass' => false,
            'isMigrate' => false,
        ]));

        self::assertSame(1, $affected);

        $user = $this->user->getById(3)->getData(UserModel::class);

        self::assertSame('new_pass', $user->getPass());
        self::assertSame('', $user->toArray(['hashSalt'])['hashSalt']);
    }

    #[Test]
    public function getUserEmailForGroupReturnsTheEnabledUsersWithEmail(): void
    {
        $result = $this->user->getUserEmailForGroup(2);

        self::assertSame(['demo', 'user_a', 'user_b'], array_map(
            static fn(UserModel $user) => $user->getLogin(),
            $result->getDataAsArray(UserModel::class)
        ));

        self::assertSame(['demo@syspass.org', 'user_a@syspass.org', 'user_b@syspass.org'], array_map(
            static fn(UserModel $user) => $user->getEmail(),
            $result->getDataAsArray(UserModel::class)
        ));
    }

    #[Test]
    public function getUsageForUserReturnsTheAccountsAndGroupsUsingTheUser(): void
    {
        $rows = $this->user->getUsageForUser(3)->getDataAsArray();

        $items = array_map(
            static fn($row) => [(int)$row->id, $row->ref],
            $rows
        );

        sort($items);

        self::assertSame(
            [
                [1, 'Account'],
                [2, 'UserGroup'],
                [3, 'Account'],
                [4, 'Account'],
            ],
            $items
        );
    }

    #[Test]
    public function searchAsAdminIncludesAdminAccounts(): void
    {
        $result = $this->user->search(new ItemSearchDto('admin'));

        self::assertContains(1, array_map(
            static fn(UserModel $user) => $user->getId(),
            $result->getDataAsArray(UserModel::class)
        ));
    }

    #[Test]
    public function searchAsNonAdminNeverReturnsAdminAccounts(): void
    {
        $this->asNonAdminUser();

        $result = $this->user->search(new ItemSearchDto('admin'));

        self::assertNotContains(1, array_map(
            static fn(UserModel $user) => $user->getId(),
            $result->getDataAsArray(UserModel::class)
        ));
    }
}
