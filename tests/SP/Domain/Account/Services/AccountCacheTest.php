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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\Account\Models\AccountItem;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Services\AccountCache;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountCacheServiceTest
 *
 */
#[Group('unitary')]
class AccountCacheTest extends UnitaryTestCase
{

    private AccountToUserRepository|MockObject      $accountToUserRepository;
    private AccountToUserGroupRepository|MockObject $accountToUserGroupRepository;
    private AccountCache                       $accountCache;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    #[Test]
    public function loadsGrantsForMultipleAccountsWithOneQueryPerGrantType(): void
    {
        $user = new AccountItem(['accountId' => 41, 'id' => 7, 'isEdit' => 1]);
        $group = new AccountItem(['accountId' => 42, 'id' => 9, 'isEdit' => 0]);
        $this->accountToUserRepository->expects(self::once())->method('getUsersByAccountIds')
            ->with([41, 42])->willReturn(new QueryResult([$user]));
        $this->accountToUserGroupRepository->expects(self::once())->method('getUserGroupsByAccountIds')
            ->with([41, 42])->willReturn(new QueryResult([$group]));
        $result = $this->accountCache->getForAccounts([41, 42]);
        self::assertSame([$user], $result[41]->getUsers());
        self::assertSame([], $result[41]->getUserGroups());
        self::assertSame([], $result[42]->getUsers());
        self::assertSame([$group], $result[42]->getUserGroups());
    }

    #[Test]
    public function chunksLargeGrantLookupsWithoutDroppingOrReorderingAccounts(): void
    {
        $firstBatch = range(1, 1000);
        $lastBatch = [1001];
        $user = new AccountItem(['accountId' => 1001, 'id' => 7]);
        $group = new AccountItem(['accountId' => 1, 'id' => 9]);
        $userBatches = [];
        $groupBatches = [];

        $this->accountToUserRepository->method('getUsersByAccountIds')
            ->willReturnCallback(static function (array $ids) use (&$userBatches, $lastBatch, $user): QueryResult {
                $userBatches[] = $ids;
                return new QueryResult($ids === $lastBatch ? [$user] : []);
            });
        $this->accountToUserGroupRepository->method('getUserGroupsByAccountIds')
            ->willReturnCallback(static function (array $ids) use (&$groupBatches, $firstBatch, $group): QueryResult {
                $groupBatches[] = $ids;
                return new QueryResult($ids === $firstBatch ? [$group] : []);
            });

        $grants = $this->accountCache->getForAccounts([...$firstBatch, 1, ...$lastBatch]);

        self::assertSame([$firstBatch, $lastBatch], $userBatches);
        self::assertSame([$firstBatch, $lastBatch], $groupBatches);
        self::assertSame(range(1, 1001), array_keys($grants));
        self::assertSame([$group], $grants[1]->getUserGroups());
        self::assertSame([], $grants[1000]->getUsers());
        self::assertSame([$user], $grants[1001]->getUsers());
    }

    #[Test]
    public function batchedGrantsAreRefreshedAfterConcurrentRevocation(): void
    {
        $user = new AccountItem(['accountId' => 41, 'id' => 7, 'isEdit' => 0]);
        $this->accountToUserRepository->expects(self::exactly(2))->method('getUsersByAccountIds')
            ->willReturnOnConsecutiveCalls(new QueryResult([$user]), new QueryResult([]));
        $this->accountToUserGroupRepository->expects(self::exactly(2))->method('getUserGroupsByAccountIds')
            ->willReturn(new QueryResult([]));
        self::assertSame([$user], $this->accountCache->getForAccounts([41])[41]->getUsers());
        self::assertSame([], $this->accountCache->getForAccounts([41])[41]->getUsers());
        self::assertSame([], $this->accountCache->getForAccounts([]));
    }

    #[Test]
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
    #[Test]
    public function equalTimestampReloadsAccountGrants(): void
    {
        $accountId = 42;
        $cached = new AccountCacheDto($accountId, [1], [2]);
        $this->context->setAccountsCache([$accountId => $cached]);
        $this->accountToUserRepository->expects(self::once())->method('getUsersByAccountId')
            ->with($accountId)->willReturn(new QueryResult([]));
        $this->accountToUserGroupRepository->expects(self::once())->method('getUserGroupsByAccountId')
            ->with($accountId)->willReturn(new QueryResult([]));

        $result = $this->accountCache->getCacheForAccount($accountId, $cached->getTime());
        self::assertSame([], $result->getUsers());
        self::assertSame([], $result->getUserGroups());
    }

    #[Test]
    public function staleSessionGrantsAreReloadedRegardlessOfClockSkew(): void
    {
        $accountId = self::$faker->randomNumber();

        $accountCacheDto = new AccountCacheDto($accountId, [1, 2, 3], [1, 2, 3]);

        $this->context->setAccountsCache([$accountId => $accountCacheDto]);

        $this->accountToUserRepository
            ->expects(self::once())
            ->method('getUsersByAccountId')->willReturn(new QueryResult([]));

        $this->accountToUserGroupRepository
            ->expects(self::once())
            ->method('getUserGroupsByAccountId')->willReturn(new QueryResult([]));

        $out = $this->accountCache->getCacheForAccount($accountId, $accountCacheDto->getTime() - 1);

        self::assertSame([], $out->getUsers());
        self::assertSame([], $out->getUserGroups());
    }

    #[Test]
    public function concurrentRevocationDoesNotPersistAnEarlierGrant(): void
    {
        $revoked = false;
        $this->accountToUserRepository->expects(self::exactly(2))->method('getUsersByAccountId')
            ->willReturnCallback(static function () use (&$revoked): QueryResult {
                $snapshot = $revoked ? [] : [123];
                // A revocation commits after this read but before the first lookup returns.
                $revoked = true;
                return new QueryResult($snapshot);
            });
        $this->accountToUserGroupRepository->expects(self::exactly(2))->method('getUserGroupsByAccountId')
            ->willReturn(new QueryResult([]));

        self::assertSame([123], $this->accountCache->getCacheForAccount(42, 1)->getUsers());
        self::assertSame([], $this->accountCache->getCacheForAccount(42, 1)->getUsers());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToUserRepository = $this->createMock(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepository::class);

        $this->accountCache = new AccountCache(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->accountToUserRepository,
            $this->accountToUserGroupRepository
        );
    }

}
