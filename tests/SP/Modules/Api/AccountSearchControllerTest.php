<?php

declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Modules\Api;

use PHPUnit\Framework\Attributes\Group;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Ports\AccountCacheService;
use SP\Domain\Account\Ports\AccountSearchService;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\User\Ports\UserToUserGroupService;
use SP\Modules\Api\Controllers\Account\SearchController;
use SP\Modules\Api\Controllers\ApiControllerHelper;
use SP\Tests\UnitaryTestCase;

#[Group('unitary')]
final class AccountSearchControllerTest extends UnitaryTestCase
{
    public function testSearchKeepsUnlimitedCountAndReloadsMembershipsOnTheNextSearch(): void
    {
        $this->context->setTrasientKey(BootstrapBase::CONTEXT_ACTION_NAME, 'search');
        $api = $this->createMock(ApiService::class);
        $api->expects(self::exactly(2))->method('setup')->with(AclActionsInterface::ACCOUNT_SEARCH);
        $api->method('getParamInt')->willReturnCallback(
            static fn(string $param, bool $required = false, ?int $default = null): ?int => $param === 'count'
                ? 0
                : $default
        );
        $api->method('getParamArray')->with('tagsId', false, [])->willReturn([]);
        $search = $this->createMock(AccountSearchService::class);
        $search->expects(self::exactly(2))->method('getByFilter')
            ->with(self::callback(static fn(AccountSearchFilterDto $filter): bool => $filter->getLimitCount() === 0))
            ->willReturn(new QueryResult([
                new AccountSearchView(['id' => 1, 'userId' => 100, 'userGroupId' => 101, 'login' => 'visible']),
                new AccountSearchView(['id' => 2, 'userId' => 100, 'userGroupId' => 102, 'login' => 'hidden']),
            ]));
        $grants = $this->createMock(AccountCacheService::class);
        $grants->expects(self::exactly(2))->method('getForAccounts')->with([1, 2])
            ->willReturn([1 => new AccountCacheDto(1, [], []), 2 => new AccountCacheDto(2, [], [])]);

        $memberships = $this->createMock(UserToUserGroupService::class);
        $memberships->expects(self::exactly(2))->method('getGroupsForUser')->with($this->context->getUserData()->id)
            ->willReturnOnConsecutiveCalls([['userGroupId' => 101]], []);
        $acl = new AccountAcl(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->createStub(AclInterface::class),
            $memberships,
            $this->appPaths,
            $this->fileSystem
        );
        $controller = new SearchController(
            new ApiControllerHelper($this->config, $this->context, $this->eventDispatcherStub),
            $api,
            $search,
            $acl,
            $grants
        );

        $first = $controller->searchAction()->extra;
        self::assertCount(2, $first);
        self::assertSame('visible', $first[0]->getLogin());
        self::assertNull($first[1]->getLogin());

        $second = $controller->searchAction()->extra;
        self::assertCount(2, $second);
        self::assertNull($second[0]->getLogin());
        self::assertNull($second[1]->getLogin());
    }
}
