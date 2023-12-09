<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Infrastructure\Account\Repositories;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Account\Repositories\AccountHistoryRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\AccountDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class AccountHistoryRepositoryTest
 *
 * @group unitary
 */
class AccountHistoryRepositoryTest extends UnitaryTestCase
{
    private DatabaseInterface|MockObject $database;
    private AccountHistoryRepository     $accountHistoryRepository;

    public function testGetById(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doSelect')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->accountHistoryRepository->getById($id);
    }

    public function testGetHistoryForAccount(): void
    {
        $id = self::$faker->randomNumber();

        $callback = new Callback(
            static function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doSelect')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->accountHistoryRepository->getHistoryForAccount($id);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
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
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->accountHistoryRepository->deleteByIdBatch($ids);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDeleteByIdBatchWithoutIds(): void
    {
        $this->database->expects(self::never())
                       ->method('doQuery');

        $this->accountHistoryRepository->deleteByIdBatch([]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdatePassword(): void
    {
        $id = self::$faker->randomNumber();
        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password, self::$faker->sha1);

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($id, $encryptedPassword) {
                $params = $arg->getQuery()->getBindValues();

                return $params['pass'] === $encryptedPassword->getPass()
                       && $params['key'] === $encryptedPassword->getKey()
                       && $params['mPassHash'] === $encryptedPassword->getHash()
                       && $params['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn($expected);

        $this->assertTrue($this->accountHistoryRepository->updatePassword($id, $encryptedPassword));
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
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->search($item);
    }

    public function testSearchWithoutString(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return count($arg->getQuery()->getBindValues()) === 0
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->search(new ItemSearchData());
    }

    public function testGetAccountsPassData(): void
    {
        $callback = new Callback(
            function (QueryData $arg) {
                return $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->getAccountsPassData();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate(): void
    {
        $dto = $this->buildAccountHistoryCreateDto();

        $expected = new QueryResult();
        $expected->setLastId(1);

        $callback = new Callback(
            static function (QueryData $arg) use ($dto) {
                $params = $arg->getQuery()->getBindValues();
                $accountData = $dto->getAccount();

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

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn($expected);

        $this->assertEquals($expected->getLastId(), $this->accountHistoryRepository->create($dto));
    }

    private function buildAccountHistoryCreateDto(): AccountHistoryCreateDto
    {
        return new AccountHistoryCreateDto(
            AccountDataGenerator::factory()->buildAccount(),
            self::$faker->boolean(),
            self::$faker->boolean(),
            self::$faker->sha1,
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
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

        $this->assertTrue($this->accountHistoryRepository->delete($id));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteNoResults(): void
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

        $this->assertFalse($this->accountHistoryRepository->delete($id));
    }

    public function testGetAll(): void
    {
        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database
            ->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn(new QueryResult());

        $this->accountHistoryRepository->getAll();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByAccountIdBatch(): void
    {
        $ids = [self::$faker->randomNumber(), self::$faker->randomNumber(), self::$faker->randomNumber()];

        $callback = new Callback(
            static function (QueryData $arg) use ($ids) {
                $values = $arg->getQuery()->getBindValues();

                return array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && array_shift($values) === array_shift($ids)
                       && $arg->getMapClassName() === Simple::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->database->expects(self::once())
                       ->method('doQuery')
                       ->with($callback)
                       ->willReturn(new QueryResult());

        $this->accountHistoryRepository->deleteByAccountIdBatch($ids);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByAccountIdBatchWithoutIds(): void
    {
        $this->database->expects(self::never())
                       ->method('doQuery');

        $this->accountHistoryRepository->deleteByAccountIdBatch([]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);

        $this->accountHistoryRepository = new AccountHistoryRepository(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            new QueryFactory('mysql')
        );
    }
}
