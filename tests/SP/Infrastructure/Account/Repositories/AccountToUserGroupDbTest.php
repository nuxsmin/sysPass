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

namespace SP\Tests\Infrastructure\Account\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Models\AccountItem;
use SP\Infrastructure\Account\Repositories\AccountToUserGroup;
use SP\Tests\DatabaseRepositoryTestCase;

#[Group('integration')]
final class AccountToUserGroupDbTest extends DatabaseRepositoryTestCase
{
    private AccountToUserGroup $accountToUserGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToUserGroup = new AccountToUserGroup(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function getUserGroupsByAccountIdReturnsTheGroupAndItsEditPermission(): void
    {
        $groups = $this->accountToUserGroup->getUserGroupsByAccountId(1)->getDataAsArray(AccountItem::class);

        self::assertCount(1, $groups);
        self::assertSame(2, $groups[0]->getId());
        self::assertSame('Demo', $groups[0]->getName());
        self::assertSame(1, $groups[0]->getIsEdit());
    }
}