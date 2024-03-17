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

namespace SPT\Domain\Account\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Builder\InvocationStubber;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\DataModel\User;
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Account\Ports\AccountSearchConstants;
use SP\Domain\Account\Ports\AccountSearchDataBuilder;
use SP\Domain\Account\Ports\AccountSearchRepository;
use SP\Domain\Account\Services\AccountSearch;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Database\QueryResult;
use SPT\Domain\Account\Services\Builders\AccountSearchTokenizerDataTrait;
use SPT\UnitaryTestCase;

/**
 * Class AccountSearchServiceTest
 *
 */
#[Group('unitary')]
class AccountSearchTest extends UnitaryTestCase
{
    use AccountSearchTokenizerDataTrait;

    private AccountSearchRepository|MockObject  $accountSearchRepository;
    private AccountSearch                       $accountSearch;
    private AccountSearchDataBuilder|MockObject $accountSearchDataBuilder;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    #[DataProvider('searchUsingStringDataProvider')]
    public function testGetByFilter(string $search)
    {
        $accountSearchFilter = AccountSearchFilterDto::build($search);
        $queryResult = new QueryResult();

        $this->accountSearchRepository
            ->expects(self::once())
            ->method('getByFilter')
            ->with($accountSearchFilter)
            ->willReturn($queryResult);

        $this->accountSearchDataBuilder
            ->expects(self::once())
            ->method('buildFrom');

        $this->accountSearch->getByFilter($accountSearchFilter);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    #[DataProvider('searchByItemDataProvider')]
    public function testGetByFilterUsingItems(string $search, array $expected)
    {
        $accountSearchFilter = AccountSearchFilterDto::build($search);
        $queryResult = new QueryResult();

        $this->accountSearchRepository
            ->expects(self::once())
            ->method('getByFilter')
            ->with($accountSearchFilter)
            ->willReturn($queryResult);

        $this->accountSearchDataBuilder
            ->expects(self::once())
            ->method('buildFrom');

        $this->buildExpectationForFilter(array_keys($expected)[0]);

        $this->accountSearch->getByFilter($accountSearchFilter);
    }

    private function buildExpectationForFilter(string $filter): InvocationStubber
    {
        switch ($filter) {
            case AccountSearchConstants::FILTER_USER_NAME:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForUser');
            case AccountSearchConstants::FILTER_OWNER:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForOwner');
            case AccountSearchConstants::FILTER_GROUP_NAME:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForGroup');
            case AccountSearchConstants::FILTER_MAIN_GROUP:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForMainGroup');
            case AccountSearchConstants::FILTER_FILE_NAME:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForFile');
            case AccountSearchConstants::FILTER_ACCOUNT_ID:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForAccountId');
            case AccountSearchConstants::FILTER_CLIENT_NAME:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForClient');
            case AccountSearchConstants::FILTER_CATEGORY_NAME:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForCategory');
            case AccountSearchConstants::FILTER_ACCOUNT_NAME_REGEX:
                return $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForAccountNameRegex');
        }

        throw new RuntimeException('Invalid filter');
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    #[DataProvider('searchByItemDataProvider')]
    public function testGetByFilterUsingItemsDoesNotThrowException(string $search, array $expected)
    {
        $accountSearchFilter = AccountSearchFilterDto::build($search);
        $queryResult = new QueryResult();

        $this->accountSearchRepository
            ->expects(self::once())
            ->method('getByFilter')
            ->with($accountSearchFilter)
            ->willReturn($queryResult);

        $this->accountSearchDataBuilder
            ->expects(self::once())
            ->method('buildFrom');

        $mock = $this->buildExpectationForFilter(array_keys($expected)[0]);
        $mock->willThrowException(new RuntimeException('test'));

        $this->accountSearch->getByFilter($accountSearchFilter);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    #[DataProvider('searchByConditionDataProvider')]
    public function testGetByFilterUsingConditions(string $search, array $expected)
    {
        $accountSearchFilter = AccountSearchFilterDto::build($search);
        $queryResult = new QueryResult();

        $this->accountSearchRepository
            ->expects(self::once())
            ->method('getByFilter')
            ->with($accountSearchFilter)
            ->willReturn($queryResult);

        $this->accountSearchDataBuilder
            ->expects(self::once())
            ->method('buildFrom');

        $this->buildExpectationForCondition(array_keys($expected)[0]);

        $this->accountSearch->getByFilter($accountSearchFilter);
    }

    private function buildExpectationForCondition(string $condition): void
    {
        switch ($condition) {
            case AccountSearchConstants::FILTER_IS_EXPIRED:
                $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForIsExpired');
                break;
            case AccountSearchConstants::FILTER_NOT_EXPIRED:
                $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForIsNotExpired');
                break;
            case AccountSearchConstants::FILTER_IS_PRIVATE:
                $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForIsPrivate')
                    ->with($this->context->getUserData()->getId(), $this->context->getUserData()->getUserGroupId());
                break;
            case AccountSearchConstants::FILTER_NOT_PRIVATE:
                $this->accountSearchRepository
                    ->expects(self::once())
                    ->method('withFilterForIsNotPrivate');
                break;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $userService = $this->createMock(UserServiceInterface::class);
        $userService
            ->method('getByLogin')
            ->willReturn(
                new User([
                                 'id' => self::$faker->randomNumber(),
                                 'userGroupId' => self::$faker->randomNumber(),
                             ])
            );

        $userGroupService = $this->createMock(UserGroupServiceInterface::class);
        $userGroupService
            ->method('getByName')
            ->willReturn(
                new UserGroup([
                                      'id' => self::$faker->randomNumber(),
                                  ])
            );

        $this->accountSearchRepository = $this->createMock(AccountSearchRepository::class);
        $this->accountSearchDataBuilder = $this->createMock(AccountSearchDataBuilder::class);

        $this->accountSearch = new AccountSearch(
            $this->application,
            $userService,
            $userGroupService,
            $this->accountSearchRepository,
            $this->accountSearchDataBuilder
        );
    }
}
