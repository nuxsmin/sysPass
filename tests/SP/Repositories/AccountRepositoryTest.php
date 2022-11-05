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

namespace SP\Tests\Repositories;

use PHPUnit\Framework\Constraint\Callback;
use SP\DataModel\AccountVData;
use SP\Domain\Account\Out\AccountPassData;
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Account\Services\AccountRequest;
use SP\Domain\Common\Out\SimpleModel;
use SP\Infrastructure\Account\Repositories\AccountRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Mvc\Model\QueryCondition;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a las cuentas
 *
 * @package SP\Tests
 */
class AccountRepositoryTest extends UnitaryTestCase
{
    private DatabaseInterface $databaseInterface;
    private AccountRepository $accountRepository;

    /**
     * @noinspection ClassMockingCorrectnessInspection
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseInterface = $this->createMock(DatabaseInterface::class);
        $this->accountRepository = new AccountRepository($this->databaseInterface, $this->context);
    }

    public function testGetTotalNumAccounts(): void
    {
        $expected = new QueryResult([new SimpleModel(['num' => 1])]);

        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === SimpleModel::class && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback, false)
            ->willReturn($expected);

        $this->assertEquals($expected->getData(), $this->accountRepository->getTotalNumAccounts());
    }

    public function testGetPasswordForId(): void
    {
        $expected = new QueryResult();

        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === AccountPassData::class && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback, false)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->accountRepository->getPasswordForId(1));
    }

    public function testGetPasswordHistoryForId(): void
    {
        $expected = new QueryResult();

        $callback = new Callback(
            static function (QueryData $arg) {
                return $arg->getMapClassName() === AccountPassData::class && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback, false)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->accountRepository->getPasswordHistoryForId(new QueryCondition()));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testIncrementDecryptCounter(): void
    {
        $id = 1;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            function (QueryData $arg) use ($id) {
                $params = $arg->getParams();

                return $params[0] === $id && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
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
            function (QueryData $arg) use ($id) {
                return $arg->getParams()[0] === $id && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
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
            function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getParams();

                return $params[0] === $accountRequest->clientId
                       && $params[1] === $accountRequest->categoryId
                       && $params[2] === $accountRequest->name
                       && $params[3] === $accountRequest->login
                       && $params[4] === $accountRequest->url
                       && $params[5] === $accountRequest->pass
                       && $params[6] === $accountRequest->key
                       && $params[7] === $accountRequest->notes
                       && $params[8] === $accountRequest->userId
                       && $params[9] === $accountRequest->userGroupId
                       && $params[10] === $accountRequest->userId
                       && $params[11] === $accountRequest->isPrivate
                       && $params[12] === $accountRequest->isPrivateGroup
                       && $params[13] === $accountRequest->passDateChange
                       && $params[14] === $accountRequest->parentId
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
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
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testEditPassword(): void
    {
        $accountRequest = $this->buildAccountRequest();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getParams();

                return $params[0] === $accountRequest->pass
                       && $params[1] === $accountRequest->key
                       && $params[2] === $accountRequest->userEditId
                       && $params[3] === $accountRequest->passDateChange
                       && $params[4] === $accountRequest->id
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
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
        $accountPasswordRequest = new AccountPasswordRequest();
        $accountPasswordRequest->pass = self::$faker->password;
        $accountPasswordRequest->key = self::$faker->password;
        $accountPasswordRequest->id = self::$faker->randomNumber();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            function (QueryData $arg) use ($accountPasswordRequest) {
                $params = $arg->getParams();

                return $params[0] === $accountPasswordRequest->pass
                       && $params[1] === $accountPasswordRequest->key
                       && $params[2] === $accountPasswordRequest->id
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountRepository->updatePassword($accountPasswordRequest));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testEditRestore(): void
    {
        $historyId = 1;
        $userId = 1;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            function (QueryData $arg) use ($historyId, $userId) {
                $params = $arg->getParams();

                return $params[0] === $historyId
                       && $params[1] === $userId
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertTrue($this->accountRepository->editRestore($historyId, $userId));
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
            function (QueryData $arg) use ($id) {
                $params = $arg->getParams();

                return $params[0] === $id
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getAffectedNumRows(), $this->accountRepository->delete($id));
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
            function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getParams();

                return $params[0] === $accountRequest->clientId
                       && $params[1] === $accountRequest->categoryId
                       && $params[2] === $accountRequest->name
                       && $params[3] === $accountRequest->login
                       && $params[4] === $accountRequest->url
                       && $params[5] === $accountRequest->notes
                       && $params[6] === $accountRequest->userEditId
                       && $params[7] === $accountRequest->passDateChange
                       && $params[8] === $accountRequest->isPrivate
                       && $params[9] === $accountRequest->isPrivateGroup
                       && $params[10] === $accountRequest->parentId
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
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
            function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getParams();

                return $params[0] === $accountRequest->clientId
                       && $params[1] === $accountRequest->categoryId
                       && $params[2] === $accountRequest->name
                       && $params[3] === $accountRequest->login
                       && $params[4] === $accountRequest->url
                       && $params[5] === $accountRequest->notes
                       && $params[6] === $accountRequest->userEditId
                       && $params[7] === $accountRequest->passDateChange
                       && $params[8] === $accountRequest->isPrivate
                       && $params[9] === $accountRequest->isPrivateGroup
                       && $params[10] === $accountRequest->parentId
                       && $params[11] === $accountRequest->userGroupId
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
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
            function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getParams();

                return $params[0] === $accountRequest->clientId
                       && $params[1] === $accountRequest->categoryId
                       && $params[2] === $accountRequest->name
                       && $params[3] === $accountRequest->login
                       && $params[4] === $accountRequest->url
                       && $params[5] === $accountRequest->notes
                       && $params[6] === $accountRequest->userEditId
                       && $params[7] === $accountRequest->passDateChange
                       && $params[8] === $accountRequest->isPrivate
                       && $params[9] === $accountRequest->isPrivateGroup
                       && $params[10] === $accountRequest->parentId
                       && $params[11] === $accountRequest->userId
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getAffectedNumRows(), $this->accountRepository->update($accountRequest));
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testUpdateBulk()
    {
        $accountRequest = $this->buildAccountRequest();

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            function (QueryData $arg) use ($accountRequest) {
                $params = $arg->getParams();

                return $params[0] === $accountRequest->userEditId
                       && $params[1] === $accountRequest->clientId
                       && $params[2] === $accountRequest->categoryId
                       && $params[3] === $accountRequest->userId
                       && $params[4] === $accountRequest->userGroupId
                       && $params[5] === $accountRequest->passDateChange
                       && $params[6] === $accountRequest->id
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doQuery')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected->getAffectedNumRows(), $this->accountRepository->updateBulk($accountRequest));
    }

    public function testgetById()
    {
        $id = self::$faker->randomNumber();

        $expected = new QueryResult();

        $callback = new Callback(
            function (QueryData $arg) use ($id) {
                $params = $arg->getParams();

                return $params[0] === $id
                       && $arg->getMapClassName() === AccountVData::class
                       && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->accountRepository->getById($id));
    }
}
