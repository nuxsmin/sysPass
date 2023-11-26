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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\Account\Ports\AccountToUserGroupRepositoryInterface;
use SP\Domain\Account\Ports\AccountToUserRepositoryInterface;
use SP\Domain\Account\Services\AccountCacheService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountCacheServiceTest
 *
 * @group unitary
 */
class AccountCacheServiceTest extends UnitaryTestCase
{

    private AccountToUserRepositoryInterface|MockObject      $accountToUserRepository;
    private AccountToUserGroupRepositoryInterface|MockObject $accountToUserGroupRepository;
    private AccountCacheService                              $accountCacheService;

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

        $out = $this->accountCacheService->getCacheForAccount($accountId, $dateEdit);

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

        $out = $this->accountCacheService->getCacheForAccount($accountId, $accountCacheDto->getTime());

        $this->assertEquals($accountCacheDto, $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToUserRepository = $this->createMock(AccountToUserRepositoryInterface::class);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepositoryInterface::class);

        $this->accountCacheService = new AccountCacheService(
            $this->application,
            $this->accountToUserRepository,
            $this->accountToUserGroupRepository
        );
    }

}
