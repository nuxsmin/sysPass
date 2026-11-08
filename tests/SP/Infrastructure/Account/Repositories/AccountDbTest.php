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
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Services\Builders\AccountFilter;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Infrastructure\Account\Repositories\Account;
use SP\Infrastructure\Account\Repositories\AccountToFavorite;
use SP\Infrastructure\Account\Repositories\AccountToTag;
use SP\Infrastructure\Account\Repositories\AccountToUser;
use SP\Infrastructure\Account\Repositories\AccountToUserGroup;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the account write queries and their cascades.
 */
#[Group('integration')]
final class AccountDbTest extends DatabaseRepositoryTestCase
{
    private Account             $account;
    private AccountToUser       $accountToUser;
    private AccountToUserGroup  $accountToUserGroup;
    private AccountToTag        $accountToTag;
    private AccountToFavorite   $accountToFavorite;

    protected function setUp(): void
    {
        parent::setUp();

        $accountFilter = new AccountFilter(
            $this->context,
            self::createStub(ConfigDataInterface::class),
            $this->queryFactory
        );

        $this->account = new Account(
            $this->db,
            $this->context,
            $this->queryFactory,
            $this->eventDispatcher,
            $accountFilter
        );

        $this->accountToUser = new AccountToUser(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
        $this->accountToUserGroup = new AccountToUserGroup(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
        $this->accountToTag = new AccountToTag(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
        $this->accountToFavorite = new AccountToFavorite(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function createStoresTheAccount(): void
    {
        $result = $this->account->create($this->buildAccountModel());

        $id = $result->getLastId();

        self::assertGreaterThan(0, $id);
        self::assertSame(1, $result->getAffectedNumRows());

        $account = $this->account->getById($id)->getData(AccountModel::class);

        self::assertSame('DB Test Account', $account->getName());
        self::assertSame('db_test', $account->getLogin());
        self::assertSame(3, $account->getUserId());
        self::assertSame(2, $account->getUserGroupId());
    }

    #[Test]
    public function updateStoresTheAccountAndItsOwnerAndGroup(): void
    {
        $id = $this->account->create($this->buildAccountModel())->getLastId();

        $updated = new AccountModel([
            'name' => 'Updated DB Account',
            'login' => 'updated_login',
            'url' => 'https://updated.example.test',
            'notes' => 'updated notes',
            'clientId' => 3,
            'categoryId' => 3,
            'otherUserEdit' => 1,
            'otherUserGroupEdit' => 1,
            'isPrivate' => 1,
            'isPrivateGroup' => 1,
            'userId' => 4,
            'userGroupId' => 3,
            'userEditId' => 3,
        ]);

        $result = $this->account->update($id, $updated, true, true);

        self::assertSame(1, $result->getAffectedNumRows());

        $account = $this->account->getById($id)->getData(AccountModel::class);

        self::assertSame('Updated DB Account', $account->getName());
        self::assertSame('updated_login', $account->getLogin());
        self::assertSame('https://updated.example.test', $account->getUrl());
        self::assertSame(3, $account->getClientId());
        self::assertSame(3, $account->getCategoryId());
        self::assertSame(4, $account->getUserId());
        self::assertSame(3, $account->getUserGroupId());
        self::assertSame(1, $account->getIsPrivate());
    }

    #[Test]
    public function deleteRemovesTheAccountAndItsRelatedRows(): void
    {
        $id = $this->account->create($this->buildAccountModel())->getLastId();

        $this->accountToUser->addByType($id, [3], true);
        $this->accountToUserGroup->addByType($id, [2], false);
        $this->accountToTag->add($id, [1, 2]);
        $this->accountToFavorite->add($id, 1);

        self::assertSame(1, $this->countRowsForAccount('AccountToUser', $id));
        self::assertSame(1, $this->countRowsForAccount('AccountToUserGroup', $id));
        self::assertSame(2, $this->countRowsForAccount('AccountToTag', $id));
        self::assertSame(1, $this->countRowsForAccount('AccountToFavorite', $id));

        $result = $this->account->delete($id);

        self::assertSame(1, $result->getAffectedNumRows());
        self::assertSame(0, $this->account->getById($id)->getNumRows());
        self::assertSame(0, $this->countRowsForAccount('AccountToUser', $id));
        self::assertSame(0, $this->countRowsForAccount('AccountToUserGroup', $id));
        self::assertSame(0, $this->countRowsForAccount('AccountToTag', $id));
        self::assertSame(0, $this->countRowsForAccount('AccountToFavorite', $id));
    }

    private function buildAccountModel(): AccountModel
    {
        return new AccountModel([
            'userGroupId' => 2,
            'userId' => 3,
            'userEditId' => 3,
            'clientId' => 2,
            'name' => 'DB Test Account',
            'categoryId' => 2,
            'login' => 'db_test',
            'url' => 'https://example.test',
            'pass' => 'encrypted_pass',
            'key' => 'encrypted_key',
            'notes' => 'DB test notes',
            'otherUserEdit' => 0,
            'otherUserGroupEdit' => 0,
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
        ]);
    }
}
