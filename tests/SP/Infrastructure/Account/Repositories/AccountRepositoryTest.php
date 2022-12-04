<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Dtos\AccountPasswordRequest;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Ports\AccountFilterUserInterface;
use SP\Domain\Common\Adapters\SimpleModel;
use SP\Infrastructure\Account\Repositories\AccountRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountRepositoryTest
 *
 * @group unitary
 */
class AccountRepositoryTest extends UnitaryTestCase
{
    private DatabaseInterface|MockObject          $database;
    private AccountRepository                     $accountRepository;
    private AccountFilterUserInterface|MockObject $accountFilterUser;

    public function testGetTotalNumAccounts(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === SimpleModel::class && !empty($arg->getQuery());
            }
        );

        $this->database->expects(self::once())
            ->method('doSelect')
            ->with($callback, false)
            ->willReturn(new QueryResult([new SimpleModel(['num' => 1])]));

        $this->accountRepository->getTotalNumAccounts();
    }

    public function testGetPasswordForId(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();

                return
                    $query->getBindValues()['id'] === 1
                    && $arg->getMapClassName() === SimpleModel::class
                    && !empty($query->getStatement());
            }
        );

        $this->accountFilterUser
            ->expects(self::once())
            ->method('buildFilter');

        $this->database->expects(self::once())
            ->method('doSelect')
            ->with($callback, false)
            ->willReturn(new QueryResult());

        $this->accountRepository->getPasswordForId(1);
    }

    public function testGetPasswordHistoryForId(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                $query = $arg->getQuery();

                return
                    $query->getBindValues()['id'] === 1
                    && $arg->getMapClassName() === SimpleModel::class
                    && !empty($query->getStatement());
            }
        );

        $this->accountFilterUser
            ->expects(self::once())
            ->method('buildFilterHistory');

        $this->database->expects(self::once())
            ->method('doSelect')
            ->with($callback, false)
            ->willReturn(new QueryResult());

        $this->accountRepository->getPasswordHistoryForId(1);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testIncrementDecryptCounter(): void
    {
        $id = 1;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountRepository->incrementDecryptCounter($id));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testIncrementDecryptCounterNoRows(): void
    {
        $id = 1;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(0);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id && !empty($arg->getQuery());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertFalse($this->accountRepository->incrementDecryptCounter($id));
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testCreate(): void
    {
        $accountRequest = $this->buildAccountRequest();

        $expected = new QueryResult();
        $expected->setLastId(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getQuery()->getBindValues();

                return $params['clientId'] === $accountRequest->clientId
                       && $params['categoryId'] === $accountRequest->categoryId
                       && $params['name'] === $accountRequest->name
                       && $params['login'] === $accountRequest->login
                       && $params['url'] === $accountRequest->url
                       && $params['pass'] === $accountRequest->pass
                       && $params['key'] === $accountRequest->key
                       && $params['notes'] === $accountRequest->notes
                       && $params['userId'] === $accountRequest->userId
                       && $params['userGroupId'] === $accountRequest->userGroupId
                       && $params['isPrivate'] === $accountRequest->isPrivate
                       && $params['isPrivateGroup'] === $accountRequest->isPrivateGroup
                       && $params['passDateChange'] === $accountRequest->passDateChange
                       && $params['parentId'] === $accountRequest->parentId
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getLastId(), $this->accountRepository->create($accountRequest));
    }

    private function buildAccountRequest(): AccountRequest
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = self::$faker->randomNumber();
        $accountRequest->name = self::$faker->name;
        $accountRequest->login = self::$faker->userName;
        $accountRequest->url = self::$faker->url;
        $accountRequest->notes = self::$faker->text;
        $accountRequest->userEditId = self::$faker->randomNumber();
        $accountRequest->passDateChange = self::$faker->unixTime;
        $accountRequest->clientId = self::$faker->randomNumber();
        $accountRequest->categoryId = self::$faker->randomNumber();
        $accountRequest->isPrivate = self::$faker->numberBetween(0, 1);
        $accountRequest->isPrivateGroup = self::$faker->numberBetween(0, 1);
        $accountRequest->parentId = self::$faker->randomNumber();
        $accountRequest->userId = self::$faker->randomNumber();
        $accountRequest->userGroupId = self::$faker->randomNumber();
        $accountRequest->key = self::$faker->text;
        $accountRequest->pass = self::$faker->text;

        return $accountRequest;
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testEditPassword(): void
    {
        $accountRequest = $this->buildAccountRequest();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getQuery()->getBindValues();

                return $params['pass'] === $accountRequest->pass
                       && $params['key'] === $accountRequest->key
                       && $params['userEditId'] === $accountRequest->userEditId
                       && $params['passDateChange'] === $accountRequest->passDateChange
                       && $params['id'] === $accountRequest->id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getAffectedNumRows(), $this->accountRepository->editPassword($accountRequest));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdatePassword(): void
    {
        $accountPasswordRequest = new AccountPasswordRequest(
            self::$faker->randomNumber(),
            new EncryptedPassword(self::$faker->password, self::$faker->password),
            self::$faker->sha1
        );

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountPasswordRequest) {
                $params = $arg->getQuery()->getBindValues();

                return $params['pass'] === $accountPasswordRequest->getEncryptedPassword()->getPass()
                       && $params['key'] === $accountPasswordRequest->getEncryptedPassword()->getKey()
                       && $params['id'] === $accountPasswordRequest->getId()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountRepository->updatePassword($accountPasswordRequest));
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testEditRestore(): void
    {
        $accountHistoryData =
            AccountHistoryData::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccountHistoryData());

        $userId = 1;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountHistoryData, $userId) {
                $params = $arg->getQuery()->getBindValues();

                return $params['id'] === $accountHistoryData->getAccountId()
                       && $params['name'] === $accountHistoryData->getName()
                       && $params['login'] === $accountHistoryData->getLogin()
                       && $params['url'] === $accountHistoryData->getUrl()
                       && $params['notes'] === $accountHistoryData->getNotes()
                       && $params['userEditId'] === $userId
                       && $params['passDateChange'] === $accountHistoryData->getPassDateChange()
                       && $params['clientId'] === $accountHistoryData->getClientId()
                       && $params['categoryId'] === $accountHistoryData->getCategoryId()
                       && $params['isPrivate'] === $accountHistoryData->getIsPrivate()
                       && $params['isPrivateGroup'] === $accountHistoryData->getIsPrivateGroup()
                       && $params['parentId'] === $accountHistoryData->getParentId()
                       && $params['userGroupId'] === $accountHistoryData->getUserGroupId()
                       && $params['key'] === $accountHistoryData->getKey()
                       && $params['pass'] === $accountHistoryData->getPass()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountRepository->editRestore($accountHistoryData, $userId));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDelete(): void
    {
        $id = 1;
        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountRepository->delete($id));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteWithouResults(): void
    {
        $id = 1;
        $expected = new QueryResult();
        $expected->setAffectedNumRows(0);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertFalse($this->accountRepository->delete($id));
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdate(): void
    {
        $accountRequest = $this->buildAccountRequest();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getQuery()->getBindValues();

                return $params['clientId'] === $accountRequest->clientId
                       && $params['categoryId'] === $accountRequest->categoryId
                       && $params['name'] === $accountRequest->name
                       && $params['login'] === $accountRequest->login
                       && $params['url'] === $accountRequest->url
                       && $params['notes'] === $accountRequest->notes
                       && $params['userEditId'] === $accountRequest->userEditId
                       && $params['passDateChange'] === $accountRequest->passDateChange
                       && $params['isPrivate'] === $accountRequest->isPrivate
                       && $params['isPrivateGroup'] === $accountRequest->isPrivateGroup
                       && $params['parentId'] === $accountRequest->parentId
                       && $params['id'] === $accountRequest->id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getAffectedNumRows(), $this->accountRepository->update($accountRequest));
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdateWithChangeGroup(): void
    {
        $accountRequest = $this->buildAccountRequest();
        $accountRequest->changeUserGroup = true;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getQuery()->getBindValues();

                return $params['clientId'] === $accountRequest->clientId
                       && $params['categoryId'] === $accountRequest->categoryId
                       && $params['name'] === $accountRequest->name
                       && $params['login'] === $accountRequest->login
                       && $params['url'] === $accountRequest->url
                       && $params['notes'] === $accountRequest->notes
                       && $params['userEditId'] === $accountRequest->userEditId
                       && $params['passDateChange'] === $accountRequest->passDateChange
                       && $params['isPrivate'] === $accountRequest->isPrivate
                       && $params['isPrivateGroup'] === $accountRequest->isPrivateGroup
                       && $params['parentId'] === $accountRequest->parentId
                       && $params['userGroupId'] === $accountRequest->userGroupId
                       && $params['id'] === $accountRequest->id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getAffectedNumRows(), $this->accountRepository->update($accountRequest));
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdateWithChangeOwner(): void
    {
        $accountRequest = $this->buildAccountRequest();
        $accountRequest->changeOwner = true;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getQuery()->getBindValues();

                return $params['clientId'] === $accountRequest->clientId
                       && $params['categoryId'] === $accountRequest->categoryId
                       && $params['name'] === $accountRequest->name
                       && $params['login'] === $accountRequest->login
                       && $params['url'] === $accountRequest->url
                       && $params['notes'] === $accountRequest->notes
                       && $params['userEditId'] === $accountRequest->userEditId
                       && $params['passDateChange'] === $accountRequest->passDateChange
                       && $params['isPrivate'] === $accountRequest->isPrivate
                       && $params['isPrivateGroup'] === $accountRequest->isPrivateGroup
                       && $params['parentId'] === $accountRequest->parentId
                       && $params['userId'] === $accountRequest->userId
                       && $params['id'] === $accountRequest->id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getAffectedNumRows(), $this->accountRepository->update($accountRequest));
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdateBulk(): void
    {
        $accountRequest = $this->buildAccountRequest();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getQuery()->getBindValues();

                return $params['userEditId'] === $accountRequest->userEditId
                       && $params['clientId'] === $accountRequest->clientId
                       && $params['categoryId'] === $accountRequest->categoryId
                       && $params['userId'] === $accountRequest->userId
                       && $params['userGroupId'] === $accountRequest->userGroupId
                       && $params['passDateChange'] === $accountRequest->passDateChange
                       && $params['id'] === $accountRequest->id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getAffectedNumRows(), $this->accountRepository->updateBulk($accountRequest));
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdateBulkNoFieldsToUpdate(): void
    {
        $this->database->expects(self::never())
            ->method('doQuery');

        $this->assertEquals(0, $this->accountRepository->updateBulk(new AccountRequest()));
    }

    public function testGetById(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->getById($id);
    }

    public function testGetAll(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->getAll();
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatch(): void
    {
        $ids = [self::$faker->randomNumber(), self::$faker->randomNumber(), self::$faker->randomNumber()];

        $callback = new Callback(
            static function (QueryData $arg) use ($ids) {
                $values = $arg->getQuery()->getBindValues();

                return array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->deleteByIdBatch($ids);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatchWithNoIds(): void
    {
        $this->database->expects(self::never())
            ->method('doQuery');

        $this->assertEquals(0, $this->accountRepository->deleteByIdBatch([]));
    }

    public function testSearch(): void
    {
        $item = new ItemSearchData(self::$faker->name);

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $params = $arg->getQuery()->getBindValues();
                $searchStringLike = '%'.$item->getSeachString().'%';

                return $params['name'] === $searchStringLike
                       && $params['clientName'] === $searchStringLike
                       && $params['categoryName'] === $searchStringLike
                       && $params['userName'] === $searchStringLike
                       && $params['userGroupName'] === $searchStringLike
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->search($item);
    }

    public function testSearchWithoutString(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return count($arg->getQuery()->getBindValues()) === 0
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->search(new ItemSearchData());
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testIncrementViewCounter(): void
    {
        $id = 1;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountRepository->incrementViewCounter($id));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testIncrementViewCounterNoRows(): void
    {
        $id = 1;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(0);

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id && !empty($arg->getQuery());
            }
        );

        $this->database->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertFalse($this->accountRepository->incrementViewCounter($id));
    }

    public function testGetDataForLink(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return $params['id'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->getDataForLink($id);
    }

    public function testGetForUser(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return $params['id'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->accountFilterUser
            ->expects(self::once())
            ->method('buildFilter');

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->getForUser($id);
    }

    public function testGetForUserWithoutAccount(): void
    {
        $callback = new Callback(
            function (QueryData $arg) {
                $params = $arg->getQuery()->getBindValues();

                return count($params) === 0
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->accountFilterUser
            ->expects(self::once())
            ->method('buildFilter');

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->getForUser();
    }

    public function testGetLinked(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            function (QueryData $arg) use ($id) {
                $params = $arg->getQuery()->getBindValues();

                return $params['parentId'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->accountFilterUser
            ->expects(self::once())
            ->method('buildFilter');

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->getLinked($id);
    }

    public function testGetAccountsPassData(): void
    {
        $callback = new Callback(
            function (QueryData $arg) {
                return $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountRepository->getAccountsPassData();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $queryFactory = new QueryFactory('mysql');

        $select = (new QueryFactory('mysql', QueryFactory::COMMON))->newSelect();
        $this->accountFilterUser = $this->createMock(AccountFilterUserInterface::class);
        $this->accountFilterUser->method('buildFilter')->willReturn($select);
        $this->accountFilterUser->method('buildFilterHistory')->willReturn($select);

        $this->accountRepository = new AccountRepository(
            $this->database,
            $this->context,
            $queryFactory,
            $this->application->getEventDispatcher(),
            $this->accountFilterUser
        );
    }
}
