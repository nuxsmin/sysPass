<?php
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

namespace SPT\Infrastructure\Account\Repositories;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Ports\AccountFilterBuilder;
use SP\Domain\Account\Ports\AccountSearchConstants;
use SP\Infrastructure\Account\Repositories\AccountSearch;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SPT\UnitaryTestCase;

/**
 * Class AccountSearchRepositoryTest
 *
 */
#[Group('unitary')]
class AccountSearchTest extends UnitaryTestCase
{

    private MockObject|DatabaseInterface    $database;
    private AccountFilterBuilder|MockObject $accountFilterUser;
    private AccountSearch                   $accountSearch;

    public function testWithFilterForOwner()
    {
        $out = $this->accountSearch->withFilterForOwner('test_owner');

        $bind = [
            'userLogin' => '%test_owner%',
            'userName' => '%test_owner%',
        ];

        $query = '(`Account`.`userLogin` LIKE :userLogin OR `Account`.`userName` LIKE :userName)';

        $this->assertEquals($bind, $out->getBindValues());

        $this->checkQueryRegex($out->getStatement(), $query);
    }

    private function checkQueryRegex(string $statement, string $query): void
    {
        $output = preg_replace('/([\n\s\\n]+)/', ' ', $statement);
        $expected = sprintf('/^SELECT.*%s$/m', preg_quote($query));

        $this->assertMatchesRegularExpression($expected, $output);
    }

    public function testWithFilterForAccountNameRegex()
    {
        $out = $this->accountSearch->withFilterForAccountNameRegex('test_account');

        $bind = ['name' => 'test_account'];
        $query = '`Account`.`name` REGEXP :name';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForIsPrivate()
    {
        $out = $this->accountSearch->withFilterForIsPrivate(123, 456);

        $bind = ['userId' => 123, 'userGroupId' => 456];
        $query = '(`Account`.`isPrivate` = 1 AND `Account`.`userId` = :userId) OR (`Account`.`isPrivateGroup` = 1 AND `Account`.`userGroupId` = :userGroupId)';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForIsNotExpired()
    {
        $out = $this->accountSearch->withFilterForIsNotExpired();

        $query = '(`Account`.`passDateChange` = 0 OR `Account`.`passDateChange` IS NULL OR UNIX_TIMESTAMP() < `Account`.`passDateChange`)';

        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForGroup()
    {
        $out = $this->accountSearch->withFilterForGroup(123);

        $bind = ['userGroupId' => 123];
        $query = '`Account`.`userGroupId` = :userGroupId OR (`Account`.`id` IN (SELECT `AccountToUserGroup`.`accountId` FROM AccountToUserGroup WHERE `AccountToUserGroup`.`accountId` = id AND `AccountToUserGroup`.`userGroupId` = :userGroupId))';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForFile()
    {
        $out = $this->accountSearch->withFilterForFile('test_file');

        $bind = ['fileName' => '%test_file%'];
        $query = '(`Account`.`id` IN (SELECT `AccountFile`.`accountId` FROM AccountFile WHERE `AccountFile`.`name` LIKE :fileName))';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForMainGroup()
    {
        $out = $this->accountSearch->withFilterForMainGroup('test_group');

        $bind = ['userGroupName' => '%test_group%'];
        $query = '`Account`.`userGroupName` LIKE :userGroupName';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForCategory()
    {
        $out = $this->accountSearch->withFilterForCategory('test_category');

        $bind = ['categoryName' => '%test_category%'];
        $query = '`Account`.`categoryName` LIKE :categoryName';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForUser()
    {
        $out = $this->accountSearch->withFilterForUser(123, 456);

        $bind = ['userId' => 123, 'userGroupId' => 456];
        $query = '(`Account`.`userId` = :userId or `Account`.`userGroupId` = :userGroupId or `Account`.`id` IN (SELECT `AccountToUser`.`accountId` FROM AccountToUser WHERE `AccountToUser`.`accountId` = `Account`.`id` AND `AccountToUser`.`userId` = :userId UNION SELECT `AccountToUserGroup`.`accountId` FROM AccountToUserGroup WHERE `AccountToUserGroup`.`accountId` = `Account`.`id` AND `AccountToUserGroup`.`userGroupId` = :userGroupId))';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForClient()
    {
        $out = $this->accountSearch->withFilterForClient('test_client');

        $bind = ['clientName' => '%test_client%'];
        $query = '`Account`.`clientName` LIKE :clientName';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testGetByFilter()
    {
        $accountSearchFilter = AccountSearchFilterDto::build('test');
        $accountSearchFilter->setCleanTxtSearch('test');
        $accountSearchFilter->setGlobalSearch(true);
        $accountSearchFilter->setSearchFavorites(true);
        $accountSearchFilter->setCategoryId(123);
        $accountSearchFilter->setClientId(456);
        $accountSearchFilter->setTagsId([1, 2, 3]);
        $accountSearchFilter->setLimitStart(1);
        $accountSearchFilter->setLimitCount(10);
        $accountSearchFilter->setSortKey(AccountSearchConstants::SORT_CATEGORY);
        $accountSearchFilter->setSortOrder(AccountSearchConstants::SORT_DIR_DESC);
        $accountSearchFilter->setFilterOperator(AccountSearchConstants::FILTER_CHAIN_AND);

        $this->accountFilterUser->expects(self::once())
                                ->method('buildFilter')
                                ->with(true, self::anything());
        $this->database->expects(self::once())
                       ->method('doSelect')
                       ->with(
                           new Callback(static function (QueryData $data) {
                               return !empty($data->getQuery()->getStatement()) &&
                                      $data->getMapClassName() === AccountSearchView::class;
                           }),
                           true
                       );

        $this->accountSearch->getByFilter($accountSearchFilter);
    }

    public function testGetByFilterWithSortViews()
    {
        $accountSearchFilter = AccountSearchFilterDto::build('test');
        $accountSearchFilter->setSortViews(true);

        $this->accountFilterUser->expects(self::once())
                                ->method('buildFilter');

        $this->database->expects(self::once())
                       ->method('doSelect')
                       ->with(
                           new Callback(static function (QueryData $data) {
                               return !empty($data->getQuery()->getStatement()) &&
                                      $data->getMapClassName() === AccountSearchView::class;
                           }),
                           true
                       );

        $this->accountSearch->getByFilter($accountSearchFilter);
    }

    public function testWithFilterForAccountId()
    {
        $out = $this->accountSearch->withFilterForAccountId(123);

        $bind = ['accountId' => 123];
        $query = '`Account`.`id` = :accountId';

        $this->assertEquals($bind, $out->getBindValues());
        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForIsNotPrivate()
    {
        $out = $this->accountSearch->withFilterForIsNotPrivate();

        $query = '(`Account`.`isPrivate` = 0 OR `Account`.`isPrivate` IS NULL) AND (`Account`.`isPrivateGroup` = 0 OR `Account`.`isPrivateGroup` IS NULL)';

        $this->checkQueryRegex($out->getStatement(), $query);
    }

    public function testWithFilterForIsExpired()
    {
        $out = $this->accountSearch->withFilterForIsExpired();

        $query = '(`Account`.`passDateChange` > 0 AND UNIX_TIMESTAMP() > `Account`.`passDateChange`)';

        $this->checkQueryRegex($out->getStatement(), $query);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $this->accountFilterUser = $this->createMock(AccountFilterBuilder::class);
        $queryFactory = new QueryFactory('mysql');

        $this->accountSearch = new AccountSearch(
            $this->database,
            $this->context,
            $this->application->getEventDispatcher(),
            $queryFactory,
            $this->accountFilterUser
        );
    }
}
