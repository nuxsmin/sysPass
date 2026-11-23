<?php
/**
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

namespace SP\Tests\Domain\Account\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Models\AccountItem;
use SP\Domain\Account\Ports\AccountHistoryRepository;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Services\AccountHistory;
use SP\Domain\Common\Adapters\DumpMode;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\UserToUserGroup;
use SP\Domain\User\Ports\UserToUserGroupRepository;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountHistoryServiceTest
 *
 */
#[Group('unitary')]
class AccountHistoryTest extends UnitaryTestCase
{

    private AccountHistory                      $accountHistory;
    private MockObject|AccountHistoryRepository $accountHistoryRepository;

    #[Test]
    public function testCreate()
    {
        $accountData = AccountDataGenerator::factory()->buildAccount();
        $dto = new AccountHistoryCreateDto(
            $accountData,
            self::$faker->boolean,
            self::$faker->boolean,
            self::$faker->sha1
        );

        $this->accountHistoryRepository->expects(self::once())->method('create')->with($dto);

        $this->accountHistory->create($dto);
    }

    #[Test]
    public function testDeleteByAccountIdBatch()
    {
        $ids = [1, 2, 3];

        $this->accountHistoryRepository->expects(self::once())->method('deleteByAccountIdBatch')->with($ids);

        $this->accountHistory->deleteByAccountIdBatch($ids);
    }

    /**
     * @throws SPException
     */
    #[Test]
    public function testGetHistoryForAccount()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('getHistoryForAccount')->with($id);

        $this->accountHistory->getHistoryForAccount($id);
    }

    /**
     * @throws SPException
     */
    #[Test]
    public function testGetAccountsPassData()
    {
        $this->accountHistoryRepository->expects(self::once())->method('getAccountsPassData');

        $this->accountHistory->getAccountsPassData();
    }

    /**
     * @throws ServiceException
     */
    #[Test]
    public function testDelete()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('delete')->with($id)->willReturn(true);

        $this->accountHistory->delete($id);
    }

    /**
     * @throws ServiceException
     */
    #[Test]
    public function testDeleteError()
    {
        $id = self::$faker->randomNumber();

        $this->accountHistoryRepository->expects(self::once())->method('delete')->with($id)->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the account');

        $this->accountHistory->delete($id);
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    #[Test]
    public function testGetById()
    {
        $id = self::$faker->randomNumber();
        $accountHistoryData =
            AccountHistoryModel::buildFromSimpleModel(AccountDataGenerator::factory()->buildAccountHistoryData());
        $queryResult = new QueryResult([$accountHistoryData]);

        $this->accountHistoryRepository->expects(self::once())->method('getById')->with($id)->willReturn($queryResult);

        $properties = [
            'usersView',
            'usersEdit',
            'otherUserGroupsView',
            'otherUserGroupsEdit',
            'tags',
            'userGroupsView',
            'userGroupsEdit',
            'isDeleted',
            'isPrivate',
            'isPrivateGroup',
            'otherUserGroupEdit',
            'otherUserEdit',
            'isModify',
            'customFields',
            'userEditName',
            'userEditLogin',
            'mPassHash',
        ];

        $current = $this->accountHistory->getById($id);

        $this->assertEquals(
            $accountHistoryData->toArray(null, $properties),
            $current->toArray($properties, DumpMode::EXCLUDE)
        );
        $this->assertEquals((bool)$accountHistoryData->getIsDeleted(), $current->isDeleted);
        $this->assertEquals((bool)$accountHistoryData->getIsPrivate(), $current->isPrivate);
        $this->assertEquals((bool)$accountHistoryData->getIsPrivateGroup(), $current->isPrivateGroup);
        $this->assertEquals((bool)$accountHistoryData->getOtherUserGroupEdit(), $current->otherUserGroupEdit);
        $this->assertEquals((bool)$accountHistoryData->getOtherUserEdit(), $current->otherUserEdit);
        $this->assertEquals((bool)$accountHistoryData->getIsModify(), $current->isModify);
    }

    /**
     * @throws NoSuchItemException
     * @throws SPException
     */
    #[Test]
    public function testGetByIdError()
    {
        $id = self::$faker->randomNumber();
        $queryResult = new QueryResult([]);

        $this->accountHistoryRepository->expects(self::once())->method('getById')->with($id)->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Error while retrieving account\'s data');

        $this->accountHistory->getById($id);
    }

    /**
     * @throws ServiceException
     */
    #[Test]
    public function testDeleteByIdBatch()
    {
        $ids = [1, 2, 3];

        $this->accountHistoryRepository
            ->expects($this->once())
            ->method('transactionAware')
            ->with(self::withResolveCallableCallback())
            ->willReturn(3);

        $this->accountHistoryRepository
            ->expects(self::once())
            ->method('deleteByIdBatch')
            ->with($ids)
            ->willReturn(3);

        $this->accountHistory->deleteByIdBatch($ids);
    }

    #[Test]
    public function testSearch()
    {
        $itemSearchData =
            new ItemSearchDto(self::$faker->text, self::$faker->randomNumber(), self::$faker->randomNumber());

        $this->accountHistoryRepository->expects(self::once())->method('search')->with($itemSearchData);

        $this->accountHistory->search($itemSearchData);
    }

    /**
     * @throws ServiceException
     */
    #[Test]
    public function testUpdatePasswordMasterPass()
    {
        $id = self::$faker->randomNumber();
        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password, self::$faker->sha1);

        $this->accountHistoryRepository->expects(self::once())
                                       ->method('updatePassword')
                                       ->with($id, $encryptedPassword)
                                       ->willReturn(true);

        $this->accountHistory->updatePasswordMasterPass($id, $encryptedPassword);
    }

    /**
     * @throws ServiceException
     */
    #[Test]
    public function testUpdatePasswordMasterPassError()
    {
        $id = self::$faker->randomNumber();
        $encryptedPassword = new EncryptedPassword(self::$faker->password, self::$faker->password, self::$faker->sha1);

        $this->accountHistoryRepository->expects(self::once())
                                       ->method('updatePassword')
                                       ->with($id, $encryptedPassword)
                                       ->willReturn(false);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while updating the password');

        $this->accountHistory->updatePasswordMasterPass($id, $encryptedPassword);
    }

    #[Test]
    public function testGetByAccountIdAndReassignAccountId(): void
    {
        $row = new AccountHistoryModel(['id' => 12, 'accountId' => 44]);

        $this->accountHistoryRepository
            ->expects(self::once())
            ->method('getByAccountId')
            ->with(44)
            ->willReturn(new QueryResult([$row]));

        $this->accountHistoryRepository
            ->expects(self::once())
            ->method('reassignAccountId')
            ->with(44, 55, '2024-01-01 00:00:00')
            ->willReturn(2);

        self::assertSame([$row], $this->accountHistory->getByAccountId(44));
        self::assertSame(2, $this->accountHistory->reassignAccountId(44, 55, '2024-01-01 00:00:00'));
    }

    #[Test]
    public function testGuardedDeleteSucceedsAndThrowsOnDeleteCountMismatch(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $customFieldDataService = $this->createMock(CustomFieldDataService::class);
        $service = $this->buildGuardedAccountHistory($accountRepository, customFieldDataService: $customFieldDataService);

        $this->context->setUserData($this->buildUserDto(['id' => 10, 'userGroupId' => 20, 'isAdminApp' => true]));

        $histRow = new AccountHistoryModel(['id' => 5, 'accountId' => 100]);
        $liveAccount = new AccountModel(['id' => 100, 'userId' => 1, 'userGroupId' => 2]);

        $this->accountHistoryRepository
            ->expects(self::exactly(2))
            ->method('transactionAware')
            ->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountHistoryRepository
            ->expects(self::exactly(2))
            ->method('getByIds')
            ->with([5])
            ->willReturn(new QueryResult([$histRow]));
        $accountRepository
            ->expects(self::exactly(2))
            ->method('getById')
            ->with(100)
            ->willReturn(new QueryResult([$liveAccount]));
        $customFieldDataService
            ->expects(self::exactly(2))
            ->method('delete')
            ->with([5], AclActionsInterface::ACCOUNT_HISTORY_VIEW);
        $this->accountHistoryRepository
            ->expects(self::exactly(2))
            ->method('deleteByIdBatch')
            ->with([5])
            ->willReturnOnConsecutiveCalls(1, 0);

        $service->delete(5);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the accounts');

        $service->delete(5);
    }

    #[Test]
    public function testGuardedDeleteByIdBatchRejectsEmptyOrInvalidIds(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $service = $this->buildGuardedAccountHistory($accountRepository);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the accounts');

        $service->deleteByIdBatch([0, -1]);
    }

    #[Test]
    public function testGuardedDeleteByIdBatchRejectsMissingHistoryRow(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $service = $this->buildGuardedAccountHistory($accountRepository);

        $this->accountHistoryRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountHistoryRepository
            ->expects(self::once())
            ->method('getByIds')
            ->with([5, 6])
            ->willReturn(new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100])]));

        $this->expectException(NoSuchItemException::class);

        $service->deleteByIdBatch([5, 6]);
    }

    #[Test]
    public function testGuardedDeleteByIdBatchEnforcesLiveAccountPrivacyAndAccess(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountToUserRepo = $this->createMock(AccountToUserRepository::class);
        $accountToGroupRepo = $this->createMock(AccountToUserGroupRepository::class);
        $userToGroupRepo = $this->createMock(UserToUserGroupRepository::class);
        $service = $this->buildGuardedAccountHistory(
            $accountRepository,
            $accountToUserRepo,
            $accountToGroupRepo,
            null,
            $userToGroupRepo
        );

        $this->context->setUserData($this->buildUserDto(['id' => 10, 'userGroupId' => 20]));

        $histRow = new AccountHistoryModel(['id' => 5, 'accountId' => 100]);
        $this->accountHistoryRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountHistoryRepository->method('getByIds')->willReturn(new QueryResult([$histRow]));
        $this->accountHistoryRepository->method('deleteByIdBatch')->willReturn(1);

        // 1) Private to another user -> rejected
        $accountRepository->expects(self::exactly(7))
            ->method('getById')
            ->with(100)
            ->willReturnOnConsecutiveCalls(
                new QueryResult([new AccountModel(['id' => 100, 'userId' => 99, 'userGroupId' => 20, 'isPrivate' => 1])]),
                new QueryResult([new AccountModel(['id' => 100, 'userId' => 10, 'userGroupId' => 99, 'isPrivateGroup' => 1])]),
                new QueryResult([new AccountModel(['id' => 100, 'userId' => 10, 'userGroupId' => 99])]),
                new QueryResult([new AccountModel(['id' => 100, 'userId' => 99, 'userGroupId' => 30])]),
                new QueryResult([new AccountModel(['id' => 100, 'userId' => 99, 'userGroupId' => 99])]),
                new QueryResult([new AccountModel(['id' => 100, 'userId' => 99, 'userGroupId' => 99])]),
                new QueryResult([new AccountModel(['id' => 100, 'userId' => 99, 'userGroupId' => 99])])
            );

        try {
            $service->deleteByIdBatch([5]);
            self::fail('Expected AccountPermissionException for private account');
        } catch (AccountPermissionException) {
        }

        // 2) Private group to another group -> rejected
        try {
            $service->deleteByIdBatch([5]);
            self::fail('Expected AccountPermissionException for private group');
        } catch (AccountPermissionException) {
        }

        // 3) Owner matches -> succeeds
        self::assertSame(1, $service->deleteByIdBatch([5]));

        // 4) Secondary user group matches live account's group -> succeeds
        $userToGroupRepo->method('getGroupsForUser')->willReturnOnConsecutiveCalls(
            new QueryResult([new UserToUserGroup(['userId' => 10, 'userGroupId' => 30])]),
            new QueryResult([]),
            new QueryResult([new UserToUserGroup(['userId' => 10, 'userGroupId' => 40])]),
            new QueryResult([])
        );
        self::assertSame(1, $service->deleteByIdBatch([5]));

        // 5) Account-to-user edit grant -> succeeds
        $accountToUserRepo->method('getUsersByAccountId')->willReturnOnConsecutiveCalls(
            new QueryResult([new AccountItem(['id' => 10, 'isEdit' => 1])]),
            new QueryResult([new AccountItem(['id' => 10, 'isEdit' => 0])]),
            new QueryResult([])
        );
        self::assertSame(1, $service->deleteByIdBatch([5]));

        // 6) Account-to-group edit grant via secondary group -> succeeds
        $accountToGroupRepo->method('getUserGroupsByAccountId')->willReturnOnConsecutiveCalls(
            new QueryResult([new AccountItem(['id' => 40, 'isEdit' => 1])]),
            new QueryResult([new AccountItem(['id' => 999, 'isEdit' => 1])])
        );
        self::assertSame(1, $service->deleteByIdBatch([5]));

        // 7) No matching owner, group, or edit grant -> rejected
        $this->expectException(AccountPermissionException::class);
        $service->deleteByIdBatch([5]);
    }

    #[Test]
    public function testGuardedDeleteByIdBatchEnforcesDeletedSnapshotAuthorizationAndCompleteness(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $userToGroupRepo = $this->createMock(UserToUserGroupRepository::class);
        $service = $this->buildGuardedAccountHistory($accountRepository, userToUserGroupRepository: $userToGroupRepo);

        $this->context->setUserData($this->buildUserDto(['id' => 10, 'userGroupId' => 20]));

        $this->accountHistoryRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $accountRepository->method('getById')->with(100)->willReturn(new QueryResult([]));
        $this->accountHistoryRepository->method('deleteByIdBatch')->willReturn(2);

        $delSnapValid = new AccountHistoryModel([
            'id' => 5,
            'accountId' => 100,
            'isDeleted' => 1,
            'userId' => 99,
            'userGroupId' => 30,
            'pass' => 'p',
            'key' => 'k',
        ]);
        $extraRow = new AccountHistoryModel(['id' => 4, 'accountId' => 100, 'isDeleted' => 0]);

        $this->accountHistoryRepository->method('getByIds')->willReturnOnConsecutiveCalls(
            new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100, 'isDeleted' => 0])]),
            new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100, 'isDeleted' => 1])]),
            new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100, 'isDeleted' => 1])]),
            new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100, 'isDeleted' => 1])]),
            new QueryResult([$delSnapValid]),
            new QueryResult([$delSnapValid]),
            new QueryResult([$delSnapValid, $extraRow])
        );

        $this->accountHistoryRepository->method('getByAccountId')->willReturnOnConsecutiveCalls(
            // 1) No deletion snapshot
            new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100, 'isDeleted' => 0])]),
            // 2) Invalid deletion snapshot (no parentId and empty pass/key)
            new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100, 'isDeleted' => 1])]),
            // 3) Private snapshot owned by another user
            new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100, 'isDeleted' => 1, 'isPrivate' => 1, 'userId' => 99, 'pass' => 'p', 'key' => 'k'])]),
            // 4) Private group snapshot owned by another group
            new QueryResult([new AccountHistoryModel(['id' => 5, 'accountId' => 100, 'isDeleted' => 1, 'isPrivateGroup' => 1, 'userGroupId' => 99, 'pass' => 'p', 'key' => 'k'])]),
            // 5) Unrelated non-admin user not in snapshot's group
            new QueryResult([$delSnapValid]),
            // 6) Deleting deletion snapshot alone while non-selected history remains
            new QueryResult([$delSnapValid, $extraRow]),
            // 7) Deleting all history including deletion snapshot via secondary group -> allowed
            new QueryResult([$delSnapValid, $extraRow])
        );

        $userToGroupRepo->method('getGroupsForUser')->willReturnOnConsecutiveCalls(
            new QueryResult([]),
            new QueryResult([new UserToUserGroup(['userId' => 10, 'userGroupId' => 30])]),
            new QueryResult([new UserToUserGroup(['userId' => 10, 'userGroupId' => 30])])
        );

        foreach (range(1, 5) as $_) {
            try {
                $service->deleteByIdBatch([5]);
                self::fail('Expected AccountPermissionException');
            } catch (AccountPermissionException) {
            }
        }

        // 6) Deleting deletion snapshot without remaining rows fails with ServiceException
        try {
            $service->deleteByIdBatch([5]);
            self::fail('Expected ServiceException when non-deleted history rows remain');
        } catch (ServiceException $e) {
            self::assertSame('Error while deleting the account', $e->getMessage());
        }

        // 7) Deleting both rows by secondary group succeeds
        self::assertSame(2, $service->deleteByIdBatch([5, 4]));
    }

    #[Test]
    public function testGuardedDeleteByIdBatchAllowsAdminOnDeletedSnapshot(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $service = $this->buildGuardedAccountHistory($accountRepository);

        $this->context->setUserData($this->buildUserDto(['id' => 1, 'userGroupId' => 1, 'isAdminApp' => true]));

        $snap = new AccountHistoryModel([
            'id' => 5,
            'accountId' => 100,
            'isDeleted' => 1,
            'userId' => 99,
            'userGroupId' => 99,
            'parentId' => 10,
        ]);

        $this->accountHistoryRepository->expects(self::once())
            ->method('transactionAware')
            ->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountHistoryRepository->expects(self::once())->method('getByIds')->willReturn(new QueryResult([$snap]));
        $accountRepository->expects(self::once())->method('getById')->with(100)->willReturn(new QueryResult([]));
        $this->accountHistoryRepository->expects(self::once())->method('getByAccountId')->with(100)->willReturn(new QueryResult([$snap]));
        $this->accountHistoryRepository->expects(self::once())->method('deleteByIdBatch')->with([5])->willReturn(1);

        self::assertSame(1, $service->deleteByIdBatch([5]));
    }

    private function buildGuardedAccountHistory(
        AccountRepository $accountRepository,
        ?AccountToUserRepository $accountToUserRepository = null,
        ?AccountToUserGroupRepository $accountToUserGroupRepository = null,
        ?CustomFieldDataService $customFieldDataService = null,
        ?UserToUserGroupRepository $userToUserGroupRepository = null
    ): AccountHistory {
        return new AccountHistory(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->accountHistoryRepository,
            $accountRepository,
            $accountToUserRepository,
            $accountToUserGroupRepository,
            $customFieldDataService,
            $userToUserGroupRepository
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function buildUserDto(array $overrides): UserDto
    {
        return UserDto::fromModel(
            UserDataGenerator::factory()->buildUserData()->mutate(array_merge([
                'isAdminApp' => false,
                'isAdminAcc' => false,
            ], $overrides))
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountHistoryRepository = $this->createMock(AccountHistoryRepository::class);

        $this->accountHistory = new AccountHistory(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->accountHistoryRepository
        );
    }
}
