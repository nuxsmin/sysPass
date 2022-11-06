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
use SP\DataModel\Dto\AccountHistoryCreateDto;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Out\AccountData;
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Common\Out\SimpleModel;
use SP\Infrastructure\Account\Repositories\AccountHistoryRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountHistoryRepositoryTest
 */
class AccountHistoryRepositoryTest extends UnitaryTestCase
{
    private DatabaseInterface        $databaseInterface;
    private AccountHistoryRepository $accountHistoryRepository;

    public function testGetById()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->getById($id);
    }

    public function testGetHistoryForAccount()
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->getHistoryForAccount($id);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDeleteByIdBatch()
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

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->deleteByIdBatch($ids);

    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDeleteByIdBatchWithoutIds()
    {
        $this->databaseInterface->expects(self::never())
            ->method('doQuery');

        $this->accountHistoryRepository->deleteByIdBatch([]);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testUpdatePassword()
    {
        $accountPasswordRequest = new AccountPasswordRequest();
        $accountPasswordRequest->pass = self::$faker->password;
        $accountPasswordRequest->key = self::$faker->password;
        $accountPasswordRequest->id = self::$faker->randomNumber();
        $accountPasswordRequest->hash = sha1(self::$faker->text());

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($accountPasswordRequest) {
                $params = $arg->getQuery()->getBindValues();

                return $params['pass'] === $accountPasswordRequest->pass
                       && $params['key'] === $accountPasswordRequest->key
                       && $params['id'] === $accountPasswordRequest->id
                       && $params['mPassHash'] === $accountPasswordRequest->hash
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountHistoryRepository->updatePassword($accountPasswordRequest));

    }

    public function testSearch()
    {
        $item = new ItemSearchData();
        $item->seachString = self::$faker->name;

        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                $params = $arg->getQuery()->getBindValues();
                $searchStringLike = '%'.$item->seachString.'%';

                return $params['name'] === $searchStringLike
                       && $params['clientName'] === $searchStringLike
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->search($item);
    }

    public function testSearchWithoutString()
    {
        $callback = new Callback(
            static function (QueryData $arg) use ($item) {
                return count($arg->getQuery()->getBindValues()) === 0
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->search(new ItemSearchData());
    }


    public function testGetAccountsPassData()
    {
        $callback = new Callback(
            function (QueryData $arg) {
                return $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->getAccountsPassData();
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreate()
    {
        $dto = $this->buildAccountHistoryCreateDto();

        $expected = new QueryResult();
        $expected->setLastId(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($dto) {
                $params = $arg->getQuery()->getBindValues();
                $accountData = $dto->getAccountData();

                return $params['clientId'] === $accountData->getClientId()
                       && $params['categoryId'] === $accountData->getCategoryId()
                       && $params['name'] === $accountData->getName()
                       && $params['login'] === $accountData->getLogin()
                       && $params['url'] === $accountData->getUrl()
                       && $params['pass'] === $accountData->getPass()
                       && $params['key'] === $accountData->getKey()
                       && $params['notes'] === $accountData->getNotes()
                       && $params['userId'] === $accountData->getUserId()
                       && $params['userGroupId'] === $accountData->getUserGroupId()
                       && $params['isPrivate'] === $accountData->getIsPrivate()
                       && $params['isPrivateGroup'] === $accountData->getIsPrivateGroup()
                       && $params['passDateChange'] === $accountData->getPassDateChange()
                       && $params['parentId'] === $accountData->getParentId()
                       && $params['accountId'] === $accountData->getId()
                       && $params['passDate'] === $accountData->getPassDate()
                       && $params['passDateChange'] === $accountData->getPassDateChange()
                       && $params['countView'] === $accountData->getCountView()
                       && $params['countDecrypt'] === $accountData->getCountDecrypt()
                       && $params['dateAdd'] === $accountData->getDateAdd()
                       && $params['dateEdit'] === $accountData->getDateEdit()
                       && $params['otherUserEdit'] === $accountData->getOtherUserEdit()
                       && $params['otherUserGroupEdit'] === $accountData->getOtherUserGroupEdit()
                       && $params['isModify'] === $dto->isModify()
                       && $params['isDeleted'] === $dto->isDelete()
                       && $params['mPassHash'] === $dto->getMasterPassHash()
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getLastId(), $this->accountHistoryRepository->create($dto));

    }

    private function buildAccountHistoryCreateDto(): AccountHistoryCreateDto
    {
        $accountRequest = new AccountData();
        $accountRequest->id = self::$faker->randomNumber();
        $accountRequest->accountId = self::$faker->randomNumber();
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
        $accountRequest->passDate = self::$faker->unixTime();
        $accountRequest->passDateChange = self::$faker->unixTime();
        $accountRequest->countView = self::$faker->randomNumber();
        $accountRequest->countDecrypt = self::$faker->randomNumber();
        $accountRequest->dateAdd = self::$faker->unixTime();
        $accountRequest->dateEdit = self::$faker->unixTime();
        $accountRequest->otherUserEdit = self::$faker->boolean();

        return new AccountHistoryCreateDto(
            $accountRequest,
            self::$faker->boolean(),
            self::$faker->boolean(),
            self::$faker->sha1,
        );
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDelete()
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

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountHistoryRepository->delete($id));

    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteNoResults()
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

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertFalse($this->accountHistoryRepository->delete($id));

    }

    public function testGetAll()
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->getAll();

    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByAccountIdBatch()
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

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->deleteByAccountIdBatch($ids);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByAccountIdBatchWithoutIds()
    {
        $this->databaseInterface->expects(self::never())
            ->method('doQuery');

        $this->accountHistoryRepository->deleteByAccountIdBatch([]);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseInterface = $this->createMock(DatabaseInterface::class);

        $this->accountHistoryRepository = new AccountHistoryRepository(
            $this->databaseInterface,
            $this->context,
            $this->application->getEventDispatcher(),
            new QueryFactory('mysql')
        );
    }
}
