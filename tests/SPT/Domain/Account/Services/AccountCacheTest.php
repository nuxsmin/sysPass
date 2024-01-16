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

use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Services\AccountCache;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Account\Repositories\AccountToUserGroup;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class AccountCacheServiceTest
 *
 * @group unitary
 */
class AccountCacheTest extends UnitaryTestCase
{

    private AccountToUserRepository|MockObject $accountToUserRepository;
    private AccountToUserGroup|MockObject      $accountToUserGroupRepository;
    private AccountCache                       $accountCache;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetCacheForAccount()
    {
        $accountId = self::$faker->randomNumber();
        $dateEdit = self::$faker->unixTime;

        $accountCacheDto = new AccountCacheDto($accountId, [1, 2, 3], [1, 2, 3]);

        $this->accountToUserRepository
            ->expects(self::once())
            ->method('getUsersByAccountId')
            ->with($accountId)
            ->willReturn(new QueryResult([1, 2, 3]));

        $this->accountToUserGroupRepository
            ->expects(self::once())
            ->method('getUserGroupsByAccountId')
            ->with($accountId)
            ->willReturn(new QueryResult([1, 2, 3]));

        $out = $this->accountCache->getCacheForAccount($accountId, $dateEdit);

        $this->assertEquals($accountCacheDto, $out);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function testGetCacheForAccountWithCacheHit()
    {
        $accountId = self::$faker->randomNumber();

        $accountCacheDto = new AccountCacheDto($accountId, [1, 2, 3], [1, 2, 3]);

        $this->context->setAccountsCache([$accountId => $accountCacheDto]);

        $this->accountToUserRepository
            ->expects(self::never())
            ->method('getUsersByAccountId');

        $this->accountToUserGroupRepository
            ->expects(self::never())
            ->method('getUserGroupsByAccountId');

        $out = $this->accountCache->getCacheForAccount($accountId, $accountCacheDto->getTime());

        $this->assertEquals($accountCacheDto, $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToUserRepository = $this->createMock(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepository::class);

        $this->accountCache = new AccountCache(
            $this->application,
            $this->accountToUserRepository,
            $this->accountToUserGroupRepository
        );
    }

}
