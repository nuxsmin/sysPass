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
