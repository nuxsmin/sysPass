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
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Ports\AccountSearchConstants;
use SP\Domain\Account\Services\Builders\AccountFilter;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\User\Dtos\UserDto;
use SP\Infrastructure\Account\Repositories\AccountSearch;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the account search query.
 */
#[Group('integration')]
final class AccountSearchDbTest extends DatabaseRepositoryTestCase
{
    private ConfigDataInterface $configData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configData = self::createStub(ConfigDataInterface::class);
        $this->context->setUserData(new UserDto(id: 3, isAdminApp: true, userGroupId: 3));
    }

    #[Test]
    public function getByFilterSearchesTheTextAcrossTheAccountColumns(): void
    {
        $filter = AccountSearchFilterDto::build('Google');
        $filter->setCleanTxtSearch('Google');

        $accounts = $this->search($filter);

        self::assertCount(1, $accounts);
        self::assertSame(1, $accounts[0]->getId());
        self::assertSame('Google', $accounts[0]->getName());
        self::assertSame('Web', $accounts[0]->getCategoryName());
        self::assertSame('Google', $accounts[0]->getClientName());
        self::assertSame('Admins', $accounts[0]->getUserGroupName());
    }

    #[Test]
    public function getByFilterCombinesCategoryAndClientWithAnd(): void
    {
        $filter = AccountSearchFilterDto::build('');
        $filter->setCategoryId(2);
        $filter->setClientId(2);

        self::assertSame([2, 3], $this->ids($this->search($filter)));
    }

    #[Test]
    public function getByFilterCombinesCategoryAndClientWithOr(): void
    {
        $filter = AccountSearchFilterDto::build('');
        $filter->setCategoryId(1);
        $filter->setClientId(2);
        $filter->setFilterOperator(AccountSearchConstants::FILTER_CHAIN_OR);

        self::assertSame([2, 3, 1], $this->ids($this->search($filter)));
    }

    #[Test]
    public function getByFilterReturnsOnlyTheFavoritesOfTheCurrentUser(): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: true, userGroupId: 1));

        $filter = AccountSearchFilterDto::build('');
        $filter->setSearchFavorites(true);

        self::assertSame([1], $this->ids($this->search($filter)));
    }

    #[Test]
    public function getByFilterRequiresEveryTagWhenTheChainIsAnd(): void
    {
        $filter = AccountSearchFilterDto::build('');
        $filter->setTagsId([1, 2]);
        $filter->setFilterOperator(AccountSearchConstants::FILTER_CHAIN_AND);

        self::assertSame([1], $this->ids($this->search($filter)));

        $filter = AccountSearchFilterDto::build('');
        $filter->setTagsId([1, 3]);
        $filter->setFilterOperator(AccountSearchConstants::FILTER_CHAIN_AND);

        self::assertSame([2], $this->ids($this->search($filter)));
    }

    #[Test]
    public function getByFilterAcceptsAnyTagWhenTheChainIsOr(): void
    {
        $filter = AccountSearchFilterDto::build('');
        $filter->setTagsId([1, 3]);
        $filter->setFilterOperator(AccountSearchConstants::FILTER_CHAIN_OR);

        self::assertSame([2, 1], $this->ids($this->search($filter)));
    }

    #[Test]
    public function getByFilterOrdersByName(): void
    {
        $filter = AccountSearchFilterDto::build('');
        $filter->setSortKey(AccountSearchConstants::SORT_NAME);
        $filter->setSortOrder(AccountSearchConstants::SORT_DIR_ASC);

        self::assertSame([2, 3, 1, 4], $this->ids($this->search($filter)));
    }

    #[Test]
    public function getByFilterPaginatesTheOrderedResults(): void
    {
        $filter = AccountSearchFilterDto::build('');
        $filter->setLimitStart(1);
        $filter->setLimitCount(2);

        self::assertSame([3, 1], $this->ids($this->search($filter)));
    }

    /**
     * @return AccountSearchView[]
     */
    private function search(AccountSearchFilterDto $filter): array
    {
        $accountFilter = new AccountFilter($this->context, $this->configData, $this->queryFactory);
        $accountSearch = new AccountSearch(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory,
            $accountFilter
        );

        return $accountSearch->getByFilter($filter)->getDataAsArray(AccountSearchView::class);
    }

    /**
     * @param AccountSearchView[] $accounts
     *
     * @return int[]
     */
    private function ids(array $accounts): array
    {
        return array_map(static fn(AccountSearchView $account) => $account->getId(), $accounts);
    }
}
