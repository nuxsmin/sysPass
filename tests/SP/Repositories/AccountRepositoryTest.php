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

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Constraint\Callback;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\AccountVData;
use SP\Domain\Account\Services\AccountFilterUser;
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Account\Services\AccountRequest;
use SP\Domain\Common\Out\SimpleModel;
use SP\Infrastructure\Account\Repositories\AccountRepository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
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
        $this->queryFactory = $this->getMockBuilder(QueryFactory::class)
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->setConstructorArgs(['mysql'])
            ->getMock();
        $this->accountFilterUser =
            $this->getMockBuilder(AccountFilterUser::class)
                ->enableOriginalConstructor()
                ->enableProxyingToOriginalMethods()
                ->setConstructorArgs(
                    [$this->application->getContext(), $this->config->getConfigData(), $this->queryFactory]
                )
                ->getMock();
        $this->accountRepository = new AccountRepository(
            $this->databaseInterface,
            $this->context,
            $this->queryFactory,
            $this->application->getEventDispatcher(),
            $this->accountFilterUser
        );
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
                return $arg->getMapClassName() === SimpleModel::class && !empty($arg->getQuery()->getStatement());
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
                return $arg->getMapClassName() === SimpleModel::class && !empty($arg->getQuery());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback, false)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->accountRepository->getPasswordHistoryForId(1));
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
                return $arg->getQuery()->getBindValues()['id'] === $id && !empty($arg->getQuery()->getStatement());
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
                return $arg->getQuery()->getBindValues()['id'] === $id && !empty($arg->getQuery());
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
                $params = $arg->getQuery()->getBindValues();

                return $params['pass'] === $accountRequest->pass
                       && $params['key'] === $accountRequest->key
                       && $params['userEditId'] === $accountRequest->userEditId
                       && $params['passDateChange'] === $accountRequest->passDateChange
                       && $params['id'] === $accountRequest->id
                       && !empty($arg->getQuery()->getStatement());
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
                $params = $arg->getQuery()->getBindValues();

                return $params['pass'] === $accountPasswordRequest->pass
                       && $params['key'] === $accountPasswordRequest->key
                       && $params['id'] === $accountPasswordRequest->id
                       && !empty($arg->getQuery()->getStatement());
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
        $accountHistoryData = new AccountHistoryData();
        $accountHistoryData->id = self::$faker->randomNumber();
        $accountHistoryData->accountId = self::$faker->randomNumber();
        $accountHistoryData->name = self::$faker->name;
        $accountHistoryData->login = self::$faker->userName;
        $accountHistoryData->url = self::$faker->url;
        $accountHistoryData->notes = self::$faker->text;
        $accountHistoryData->userEditId = self::$faker->randomNumber();
        $accountHistoryData->passDateChange = self::$faker->unixTime;
        $accountHistoryData->clientId = self::$faker->randomNumber();
        $accountHistoryData->categoryId = self::$faker->randomNumber();
        $accountHistoryData->isPrivate = self::$faker->numberBetween(0, 1);
        $accountHistoryData->isPrivateGroup = self::$faker->numberBetween(0, 1);
        $accountHistoryData->parentId = self::$faker->randomNumber();
        $accountHistoryData->userId = self::$faker->randomNumber();
        $accountHistoryData->userGroupId = self::$faker->randomNumber();
        $accountHistoryData->key = self::$faker->text;
        $accountHistoryData->pass = self::$faker->text;

        $userId = 1;

        $expected = new QueryResult();
        $expected->setAffectedNumRows(1);

        $callback = new Callback(
            function (QueryData $arg) use ($accountHistoryData, $userId) {
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

        $this->databaseInterface->expects(self::once())
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
            function (QueryData $arg) use ($id) {
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && !empty($arg->getQuery()->getStatement());
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
                       && !empty($arg->getQuery()->getStatement());
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
                       && !empty($arg->getQuery()->getStatement());
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
                       && !empty($arg->getQuery()->getStatement());
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
                return $arg->getQuery()->getBindValues()['id'] === $id
                       && $arg->getMapClassName() === SimpleModel::class
                       && !empty($arg->getQuery()->getStatement());
            }
        );

        $this->databaseInterface->expects(self::once())
            ->method('doSelect')
            ->with($callback)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->accountRepository->getById($id));
    }
}
