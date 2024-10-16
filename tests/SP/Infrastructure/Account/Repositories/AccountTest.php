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

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Account\Ports\AccountFilterBuilder;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Infrastructure\Account\Repositories\Account;
use SP\Infrastructure\Database\QueryData;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountRepositoryTest
 *
 */
#[Group('unitary')]
class AccountTest extends UnitaryTestCase
{
    private DatabaseInterface|MockObject    $database;
    private Account                         $account;
    private AccountFilterBuilder|MockObject $accountFilterUser;

    public function testGetTotalNumAccounts(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === Simple::class && !empty($arg->getQuery());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback, false);

        $this->account->getTotalNumAccounts();
    }

    public function testGetPasswordForId(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['id'] === 1
                       && $arg->getMapClassName() === AccountPassItemWithIdAndName::class
                       && !empty($query->getStatement());
            }
        );

        $this->accountFilterUser
            ->expects(self::once())
            ->method('buildFilter');

        $this->database->expects(self::once())->method('runQuery')->with($callback, false);

        $this->account->getPasswordForId(1);
    }

    public function testGetPasswordHistoryForId(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();
                $params = $query->getBindValues();

                return count($params) === 1
                       && $params['accountId'] === 1
                       && $arg->getMapClassName() === AccountPassItemWithIdAndName::class
                       && !empty($query->getStatement());
            }
        );

        $this->accountFilterUser
            ->expects(self::once())
            ->method('buildFilterHistory');

        $this->database->expects(self::once())->method('runQuery')->with($callback, false);

        $this->account->getPasswordHistoryForId(1);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testIncrementDecryptCounter(): void
    {
        $id = 1;

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->incrementDecryptCounter($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testIncrementDecryptCounterNoRows(): void
    {
        $id = 1;

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && !empty($arg->getQuery());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->incrementDecryptCounter($id);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testCreate(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 18
                       && $params['userId'] === $account->getUserId()
                       && $params['userGroupId'] === $account->getUserGroupId()
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['name'] === $account->getName()
                       && $params['clientId'] === $account->getClientId()
                       && $params['categoryId'] === $account->getCategoryId()
                       && $params['login'] === $account->getLogin()
                       && $params['url'] === $account->getUrl()
                       && $params['pass'] === $account->getPass()
                       && $params['key'] === $account->getKey()
                       && $params['notes'] === $account->getNotes()
                       && $params['isPrivate'] === $account->getIsPrivate()
                       && $params['isPrivateGroup'] === $account->getIsPrivateGroup()
                       && $params['passDate'] === $account->getPassDate()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['parentId'] === $account->getParentId()
                       && $params['otherUserEdit'] === $account->getOtherUserEdit()
                       && $params['otherUserGroupEdit'] === $account->getOtherUserGroupEdit()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->create($account);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testEditPassword(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 5
                       && $params['pass'] === $account->getPass()
                       && $params['key'] === $account->getKey()
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['id'] === $account->getId()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->editPassword($account->getId(), $account);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdatePassword(): void
    {
        $id = self::$faker->randomNumber();
        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password);

        $callback = new Callback(
            static function (QueryData $arg) use ($id, $encryptedPassword) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 3
                       && $params['pass'] === $encryptedPassword->getPass()
                       && $params['key'] === $encryptedPassword->getKey()
                       && $params['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->updatePassword($id, $encryptedPassword);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testEditRestore(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 18
                       && $params['id'] === $account->getId()
                       && $params['userId'] === $account->getUserId()
                       && $params['userGroupId'] === $account->getUserGroupId()
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['name'] === $account->getName()
                       && $params['clientId'] === $account->getClientId()
                       && $params['categoryId'] === $account->getCategoryId()
                       && $params['login'] === $account->getLogin()
                       && $params['url'] === $account->getUrl()
                       && $params['pass'] === $account->getPass()
                       && $params['key'] === $account->getKey()
                       && $params['notes'] === $account->getNotes()
                       && $params['isPrivate'] === $account->getIsPrivate()
                       && $params['isPrivateGroup'] === $account->getIsPrivateGroup()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['parentId'] === $account->getParentId()
                       && $params['otherUserGroupEdit'] === $account->getOtherUserGroupEdit()
                       && $params['otherUserEdit'] === $account->getOtherUserEdit()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->restoreModified($account->getId(), $account);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete(): void
    {
        $id = 1;

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->delete($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteWithouResults(): void
    {
        $id = 1;

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->delete($id);
    }

    /**
     * @throws SPException
     */
    public function testUpdate(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 16
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['name'] === $account->getName()
                       && $params['clientId'] === $account->getClientId()
                       && $params['categoryId'] === $account->getCategoryId()
                       && $params['login'] === $account->getLogin()
                       && $params['url'] === $account->getUrl()
                       && $params['notes'] === $account->getNotes()
                       && $params['isPrivate'] === $account->getIsPrivate()
                       && $params['isPrivateGroup'] === $account->getIsPrivateGroup()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['parentId'] === $account->getParentId()
                       && $params['otherUserEdit'] === $account->getOtherUserEdit()
                       && $params['otherUserGroupEdit'] === $account->getOtherUserGroupEdit()
                       && $params['userGroupId'] === $account->getUserGroupId()
                       && $params['userId'] === $account->getUserId()
                       && $params['id'] === $account->getId()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->update($account->getId(), $account, true, true);
    }

    /**
     * @throws SPException
     */
    public function testUpdateWithoutGroup(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 15
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['name'] === $account->getName()
                       && $params['clientId'] === $account->getClientId()
                       && $params['categoryId'] === $account->getCategoryId()
                       && $params['login'] === $account->getLogin()
                       && $params['url'] === $account->getUrl()
                       && $params['notes'] === $account->getNotes()
                       && $params['isPrivate'] === $account->getIsPrivate()
                       && $params['isPrivateGroup'] === $account->getIsPrivateGroup()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['parentId'] === $account->getParentId()
                       && $params['otherUserEdit'] === $account->getOtherUserEdit()
                       && $params['otherUserGroupEdit'] === $account->getOtherUserGroupEdit()
                       && $params['userId'] === $account->getUserId()
                       && $params['id'] === $account->getId()
                       && !isset($params['userGroupId'])
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->update($account->getId(), $account, true, false);
    }

    /**
     * @throws SPException
     */
    public function testUpdateWithoutOwner(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 15
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['name'] === $account->getName()
                       && $params['clientId'] === $account->getClientId()
                       && $params['categoryId'] === $account->getCategoryId()
                       && $params['login'] === $account->getLogin()
                       && $params['url'] === $account->getUrl()
                       && $params['notes'] === $account->getNotes()
                       && $params['isPrivate'] === $account->getIsPrivate()
                       && $params['isPrivateGroup'] === $account->getIsPrivateGroup()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['parentId'] === $account->getParentId()
                       && $params['otherUserEdit'] === $account->getOtherUserEdit()
                       && $params['otherUserGroupEdit'] === $account->getOtherUserGroupEdit()
                       && $params['userGroupId'] === $account->getUserGroupId()
                       && $params['id'] === $account->getId()
                       && !isset($params['userId'])
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->update($account->getId(), $account, false, true);
    }

    /**
     * @throws SPException
     */
    public function testUpdateBulk(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 7
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['clientId'] === $account->getClientId()
                       && $params['categoryId'] === $account->getCategoryId()
                       && $params['userId'] === $account->getUserId()
                       && $params['userGroupId'] === $account->getUserGroupId()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['id'] === $account->getId()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('runQuery')
                       ->with($callback);

        $this->account->updateBulk($account->getId(), $account, true, true);
    }

    /**
     * @throws SPException
     */
    public function testUpdateBulkWithoutOwner(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 6
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['clientId'] === $account->getClientId()
                       && $params['categoryId'] === $account->getCategoryId()
                       && !isset($params['userId'])
                       && $params['userGroupId'] === $account->getUserGroupId()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['id'] === $account->getId()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('runQuery')
                       ->with($callback);

        $this->account->updateBulk($account->getId(), $account, false, true);

        $this->assertTrue(true);
    }

    /**
     * @throws SPException
     */
    public function testUpdateBulkWithoutGroup(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $callback = new Callback(
            static function (QueryData $arg) use ($account) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 6
                       && $params['userEditId'] === $account->getUserEditId()
                       && $params['clientId'] === $account->getClientId()
                       && $params['categoryId'] === $account->getCategoryId()
                       && $params['userId'] === $account->getUserId()
                       && $params['passDateChange'] === $account->getPassDateChange()
                       && $params['id'] === $account->getId()
                       && !isset($params['userGroupId'])
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('runQuery')
                       ->with($callback);

        $this->account->updateBulk($account->getId(), $account, true, false);
    }

    /**
     * @throws SPException
     */
    public function testUpdateBulkNoFieldsToUpdate(): void
    {
        $this->database->expects(self::never())->method('runQuery');

        $this->account->updateBulk(0, new AccountModel(), false, false);
    }

    public function testGetById(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && $arg->getMapClassName() === AccountModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->getById($id);
    }

    public function testGetByIdEnriched(): void
    {
        $id = self::$faker->randomNumber(2);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && $arg->getMapClassName() === AccountView::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->getByIdEnriched($id);
    }

    public function testGetAll(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === AccountModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch(): void
    {
        $ids = [self::$faker->randomNumber(2), self::$faker->randomNumber(2), self::$faker->randomNumber(2)];

        $callback = new Callback(
            static function (QueryData $arg) use ($ids) {
                $values = $arg->getQuery()->getBindValues();

                return count($values) === 3
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->deleteByIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatchWithNoIds(): void
    {
        $this->database->expects(self::never())->method('runQuery');

        $this->account->deleteByIdBatch([]);
    }

    public function testSearch(): void
    {
        $item = new ItemSearchDto(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $params = $arg->getQuery()->getBindValues();
                $searchStringLike = '%' . $item->getSeachString() . '%';

                return count($params) === 5
                       && $params['name'] === $searchStringLike
                       && $params['clientName'] === $searchStringLike
                       && $params['categoryName'] === $searchStringLike
                       && $params['userName'] === $searchStringLike
                       && $params['userGroupName'] === $searchStringLike
                       && $arg->getMapClassName() === AccountSearchView::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback, true);

        $this->account->search($item);
    }

    public function testSearchWithoutString(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return count($arg->getQuery()->getBindValues()) === 0
                       && $arg->getMapClassName() === AccountSearchView::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback, true);

        $this->account->search(new ItemSearchDto());
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testIncrementViewCounter(): void
    {
        $id = 1;

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->incrementViewCounter($id);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testIncrementViewCounterNoRows(): void
    {
        $id = 1;

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->incrementViewCounter($id);
    }

    public function testGetDataForLink(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->getDataForLink($id);
    }

    public function testGetForUser(): void
    {
        $id = self::$faker->randomNumber(2);

        $callback = new Callback(
            function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['id'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->accountFilterUser->expects(self::once())->method('buildFilter');

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->getForUser($id);
    }

    public function testGetForUserWithoutAccount(): void
    {
        $callback = new Callback(
            function (QueryData $arg) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 0
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->accountFilterUser->expects(self::once())->method('buildFilter');

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->getForUser();
    }

    public function testGetLinked(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 1
                       && $params['parentId'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->accountFilterUser->expects(self::once())->method('buildFilter');

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->getLinked($id);
    }

    public function testGetAccountsPassData(): void
    {
        $callback = new Callback(
            function (QueryData $arg) {
                return $arg->getMapClassName() === AccountModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())->method('runQuery')->with($callback);

        $this->account->getAccountsPassData();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $select = (new QueryFactory('mysql', QueryFactory::COMMON))->newSelect();
        $this->accountFilterUser = $this->createMock(AccountFilterBuilder::class);
        $this->accountFilterUser->method('buildFilter')->willReturn($select);
        $this->accountFilterUser->method('buildFilterHistory')->willReturn($select);

        $this->account = new Account(
            $this->database,
            $this->context,
            $queryFactory,
            $this->application->getEventDispatcher(),
            $this->accountFilterUser
        );
    }
}
