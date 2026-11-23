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
use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountHistoryRepository;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Services\AccountReader;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\UnitaryTestCase;

#[Group('unitary')]
class AccountReaderTest extends UnitaryTestCase
{
    private AccountRepository|MockObject $accountRepository;
    private AccountAclService|MockObject $accountAclService;
    private AccountToUserRepository|MockObject $accountToUserRepository;
    private AccountToUserGroupRepository|MockObject $accountToUserGroupRepository;
    private AclInterface|MockObject $acl;
    private AccountHistoryRepository|MockObject $accountHistoryRepository;
    private AccountReader $reader;

    #[Test]
    public function testCanEditReturnsFalseWhenNotVisible(): void
    {
        $this->accountRepository->expects(self::once())->method('isVisible')->with(10)->willReturn(false);
        $this->accountRepository->expects(self::never())->method('getById');

        self::assertFalse($this->reader->canEdit(10));
    }

    #[Test]
    public function testCanEditReturnsFalseWhenAccountMissing(): void
    {
        $this->accountRepository->expects(self::once())->method('isVisible')->with(10)->willReturn(true);
        $this->accountRepository->expects(self::once())->method('getById')->with(10)->willReturn(new QueryResult([]));

        self::assertFalse($this->reader->canEdit(10));
    }

    #[Test]
    public function testCanEditDelegatesToAccountAclService(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 10,
            'userId' => 3,
            'userGroupId' => 4,
            'dateEdit' => '2024-01-02 03:04:05',
            'isPrivate' => 1,
            'isPrivateGroup' => 0,
        ]);

        $permission = $this->createMock(AccountPermission::class);
        $permission->expects(self::once())
            ->method('checkAccountAccess')
            ->with(AclActionsInterface::ACCOUNT_EDIT)
            ->willReturn(true);

        $this->accountRepository->expects(self::once())->method('isVisible')->with(10)->willReturn(true);
        $this->accountRepository->expects(self::once())->method('getById')->with(10)->willReturn(new QueryResult([$account]));
        $this->accountToUserRepository->expects(self::once())->method('getUsersByAccountId')->with(10)->willReturn(new QueryResult([]));
        $this->accountToUserGroupRepository->expects(self::once())->method('getUserGroupsByAccountId')->with(10)->willReturn(new QueryResult([]));
        $this->accountAclService->expects(self::once())
            ->method('getAcl')
            ->with(AclActionsInterface::ACCOUNT_EDIT, self::isInstanceOf(AccountAclDto::class))
            ->willReturn($permission);

        self::assertTrue($this->reader->canEdit(10));
    }

    #[Test]
    public function testGetPasswordForIdRejectsWhenAclDeniesViewPass(): void
    {
        $this->acl->expects(self::once())
            ->method('checkUserAccess')
            ->with(AclActionsInterface::ACCOUNT_VIEW_PASS)
            ->willReturn(false);

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordForId(10);
    }

    #[Test]
    public function testGetPasswordForIdRejectsSelfLinkedParent(): void
    {
        $selfChild = new AccountPassItemWithIdAndName([
            'id' => 10,
            'parentId' => 10,
            'pass' => 'p',
            'key' => 'k',
        ]);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::once())
            ->method('getPasswordForId')
            ->with(10)
            ->willReturn(new QueryResult([$selfChild]));

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordForId(10);
    }

    #[Test]
    public function testGetPasswordForIdRejectsChainedOrEmptyParent(): void
    {
        $child = new AccountPassItemWithIdAndName([
            'id' => 20,
            'parentId' => 10,
        ]);
        $chainedParent = new AccountPassItemWithIdAndName([
            'id' => 10,
            'parentId' => 5,
            'pass' => 'p',
            'key' => 'k',
        ]);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::exactly(2))
            ->method('getPasswordForId')
            ->willReturnCallback(static fn(int $id): QueryResult => new QueryResult([$id === 20 ? $child : $chainedParent]));

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordForId(20);
    }

    #[Test]
    public function testGetPasswordHistoryForIdRejectsWhenAclDenies(): void
    {
        $this->acl->expects(self::once())
            ->method('checkUserAccess')
            ->with(AclActionsInterface::ACCOUNT_HISTORY_VIEW_PASS)
            ->willReturn(false);

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordHistoryForId(99);
    }

    #[Test]
    public function testGetPasswordHistoryForIdThrowsNoSuchItemWhenFallbackHistoryRowMissing(): void
    {
        $histPass = new AccountPassItemWithIdAndName([
            'id' => 99,
            'accountId' => 0,
            'parentId' => 0,
        ]);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::once())
            ->method('getPasswordHistoryForId')
            ->with(99)
            ->willReturn(new QueryResult([$histPass]));
        $this->accountHistoryRepository->expects(self::once())
            ->method('getById')
            ->with(99)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);

        $this->reader->getPasswordHistoryForId(99);
    }

    #[Test]
    public function testGetPasswordHistoryForIdRejectsInvalidResolvedAccountId(): void
    {
        $histPass = new AccountPassItemWithIdAndName([
            'id' => 99,
            'accountId' => 0,
            'parentId' => 0,
        ]);
        $histRow = new AccountHistoryModel(['id' => 99, 'accountId' => 0]);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::once())
            ->method('getPasswordHistoryForId')
            ->with(99)
            ->willReturn(new QueryResult([$histPass]));
        $this->accountHistoryRepository->expects(self::once())
            ->method('getById')
            ->with(99)
            ->willReturn(new QueryResult([$histRow]));

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordHistoryForId(99);
    }

    #[Test]
    public function testGetPasswordHistoryForIdRejectsInvisibleLiveAccount(): void
    {
        $histPass = new AccountPassItemWithIdAndName([
            'id' => 99,
            'accountId' => 15,
            'parentId' => 0,
        ]);
        $liveAccount = new AccountModel(['id' => 15]);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::once())
            ->method('getPasswordHistoryForId')
            ->with(99)
            ->willReturn(new QueryResult([$histPass]));
        $this->accountRepository->expects(self::once())
            ->method('getById')
            ->with(15)
            ->willReturn(new QueryResult([$liveAccount]));
        $this->accountRepository->expects(self::once())
            ->method('isVisible')
            ->with(15)
            ->willReturn(false);

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordHistoryForId(99);
    }

    #[Test]
    public function testGetPasswordHistoryForIdRejectsDeletedAccountWithoutSingleSnapshot(): void
    {
        $histPass = new AccountPassItemWithIdAndName(['id' => 99, 'accountId' => 15, 'parentId' => 0]);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::once())->method('getPasswordHistoryForId')->willReturn(new QueryResult([$histPass]));
        $this->accountRepository->expects(self::once())->method('getById')->with(15)->willReturn(new QueryResult([]));
        $this->accountHistoryRepository->expects(self::once())
            ->method('getByAccountId')
            ->with(15)
            ->willReturn(new QueryResult([new AccountHistoryModel(['id' => 99, 'accountId' => 15, 'isDeleted' => 0])]));

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordHistoryForId(99);
    }

    #[Test]
    public function testGetPasswordHistoryForIdRejectsInvalidDeletedSnapshotPayload(): void
    {
        $histPass = new AccountPassItemWithIdAndName(['id' => 99, 'accountId' => 15, 'parentId' => 0]);
        $invalidSnap = new AccountHistoryModel([
            'id' => 99,
            'accountId' => 15,
            'isDeleted' => 1,
            'parentId' => 0,
            'pass' => '',
            'key' => '',
        ]);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::once())->method('getPasswordHistoryForId')->willReturn(new QueryResult([$histPass]));
        $this->accountRepository->expects(self::once())->method('getById')->with(15)->willReturn(new QueryResult([]));
        $this->accountHistoryRepository->expects(self::once())->method('getByAccountId')->with(15)->willReturn(new QueryResult([$invalidSnap]));

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordHistoryForId(99);
    }

    #[Test]
    public function testGetPasswordHistoryForIdRejectsWhenAclDeniesDeletedSnapshot(): void
    {
        $histPass = new AccountPassItemWithIdAndName(['id' => 99, 'accountId' => 15, 'parentId' => 0]);
        $validSnap = new AccountHistoryModel([
            'id' => 99,
            'accountId' => 15,
            'isDeleted' => 1,
            'pass' => 'p',
            'key' => 'k',
            'userId' => 1,
            'userGroupId' => 2,
        ]);

        $permission = $this->createMock(AccountPermission::class);
        $permission->expects(self::once())
            ->method('checkAccountAccess')
            ->with(AclActionsInterface::ACCOUNT_VIEW)
            ->willReturn(false);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::once())->method('getPasswordHistoryForId')->willReturn(new QueryResult([$histPass]));
        $this->accountRepository->expects(self::once())->method('getById')->with(15)->willReturn(new QueryResult([]));
        $this->accountHistoryRepository->expects(self::once())->method('getByAccountId')->with(15)->willReturn(new QueryResult([$validSnap]));
        $this->accountAclService->expects(self::once())
            ->method('getAcl')
            ->with(AclActionsInterface::ACCOUNT_VIEW, self::isInstanceOf(AccountAclDto::class), true)
            ->willReturn($permission);

        $this->expectException(AccountPermissionException::class);

        $this->reader->getPasswordHistoryForId(99);
    }

    #[Test]
    public function testGetPasswordHistoryForIdResolvesParentPasswordForAuthorizedDeletedSnapshot(): void
    {
        $histPass = new AccountPassItemWithIdAndName([
            'id' => 99,
            'accountId' => 15,
            'parentId' => 10,
            'pass' => '',
            'key' => '',
        ]);
        $validSnap = new AccountHistoryModel([
            'id' => 99,
            'accountId' => 15,
            'isDeleted' => 1,
            'parentId' => 10,
            'userId' => 1,
            'userGroupId' => 2,
        ]);
        $parentPass = new AccountPassItemWithIdAndName([
            'id' => 10,
            'parentId' => 0,
            'pass' => 'parent-pass',
            'key' => 'parent-key',
        ]);

        $permission = $this->createMock(AccountPermission::class);
        $permission->expects(self::once())
            ->method('checkAccountAccess')
            ->with(AclActionsInterface::ACCOUNT_VIEW)
            ->willReturn(true);

        $this->acl->expects(self::once())->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->expects(self::once())->method('getPasswordHistoryForId')->willReturn(new QueryResult([$histPass]));
        $this->accountRepository->expects(self::once())->method('getById')->with(15)->willReturn(new QueryResult([]));
        $this->accountHistoryRepository->expects(self::once())->method('getByAccountId')->with(15)->willReturn(new QueryResult([$validSnap]));
        $this->accountAclService->expects(self::once())->method('getAcl')->willReturn($permission);
        $this->accountRepository->expects(self::once())
            ->method('getPasswordForId')
            ->with(10)
            ->willReturn(new QueryResult([$parentPass]));

        $resolved = $this->reader->getPasswordHistoryForId(99);

        self::assertSame('parent-pass', $resolved->getPass());
        self::assertSame('parent-key', $resolved->getKey());
    }

    #[Test]
    public function testGetAllForExport(): void
    {
        $account = AccountDataGenerator::factory()->buildAccount();

        $this->accountRepository->expects(self::once())
            ->method('getAllForExport')
            ->willReturn(new QueryResult([$account]));

        self::assertSame([$account], $this->reader->getAllForExport());
    }

    #[Test]
    public function testGetDataForLinkRejectsLinkedAccountWithoutViewPassPermission(): void
    {
        $linkData = new Simple(['id' => 20, 'parentId' => 10]);

        $this->accountRepository->expects(self::once())
            ->method('getDataForLink')
            ->with(20)
            ->willReturn(new QueryResult([$linkData]));
        $this->acl->expects(self::once())
            ->method('checkUserAccess')
            ->with(AclActionsInterface::ACCOUNT_VIEW_PASS)
            ->willReturn(false);

        $this->expectException(AccountPermissionException::class);

        $this->reader->getDataForLink(20);
    }

    #[Test]
    public function testGetDataForLinkResolvesLinkedParentPassword(): void
    {
        $linkData = new Simple(['id' => 20, 'parentId' => 10, 'pass' => '', 'key' => '']);
        $parentPass = new AccountPassItemWithIdAndName([
            'id' => 10,
            'parentId' => 0,
            'pass' => 'parent-pass',
            'key' => 'parent-key',
        ]);

        $this->accountRepository->expects(self::once())
            ->method('getDataForLink')
            ->with(20)
            ->willReturn(new QueryResult([$linkData]));
        $this->acl->expects(self::once())
            ->method('checkUserAccess')
            ->with(AclActionsInterface::ACCOUNT_VIEW_PASS)
            ->willReturn(true);
        $this->accountRepository->expects(self::once())
            ->method('getPasswordForId')
            ->with(10)
            ->willReturn(new QueryResult([$parentPass]));

        $out = $this->reader->getDataForLink(20);

        self::assertSame('parent-pass', $out['pass']);
        self::assertSame('parent-key', $out['key']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->accountAclService = $this->createMock(AccountAclService::class);
        $this->accountToUserRepository = $this->createMock(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepository::class);
        $this->acl = $this->createMock(AclInterface::class);
        $this->accountHistoryRepository = $this->createMock(AccountHistoryRepository::class);

        $this->reader = new AccountReader(
            $this->accountRepository,
            $this->accountAclService,
            $this->accountToUserRepository,
            $this->accountToUserGroupRepository,
            $this->acl,
            $this->accountHistoryRepository
        );
    }
}
