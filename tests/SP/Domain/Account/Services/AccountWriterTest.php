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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\StatelessMasterKeyStore;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountHistoryDto;
use SP\Domain\Account\Dtos\AccountUpdateBulkDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Models\AccountItem;
use SP\Domain\Account\Ports\AccountCryptService;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountItemsService;
use SP\Domain\Account\Ports\AccountPresetService;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Account\Ports\PublicLinkRepository;
use SP\Domain\Account\Services\AccountReader;
use SP\Domain\Account\Services\AccountWriter;
use SP\Domain\Category\Ports\CategoryRepository;
use SP\Domain\Client\Ports\ClientRepository;
use SP\Domain\Common\Models\Item;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\ItemPreset\Models\AccountPrivate;
use SP\Domain\ItemPreset\Models\ItemPreset;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserToUserGroup;
use SP\Domain\User\Ports\UserGroupRepository;
use SP\Domain\User\Ports\UserRepository;
use SP\Domain\User\Ports\UserToUserGroupRepository;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

#[Group('unitary')]
class AccountWriterTest extends UnitaryTestCase
{
    private AccountRepository|MockObject $accountRepository;
    private ItemPresetService|MockObject $itemPresetService;
    private AccountHistoryService|MockObject $accountHistoryService;
    private AccountItemsService|MockObject $accountItemsService;
    private AccountPresetService|MockObject $accountPresetService;
    private ConfigService|MockObject $configService;
    private AccountCryptService|MockObject $accountCryptService;
    private CustomFieldDataService|MockObject $customFieldDataService;
    private PublicLinkRepository|MockObject $publicLinkRepository;
    private ClientRepository|MockObject $clientRepository;
    private CategoryRepository|MockObject $categoryRepository;
    private UserRepository|MockObject $userRepository;
    private UserGroupRepository|MockObject $userGroupRepository;
    private AccountToTagRepository|MockObject $accountToTagRepository;
    private AccountToUserRepository|MockObject $accountToUserRepository;
    private AccountToUserGroupRepository|MockObject $accountToUserGroupRepository;
    private AclInterface|MockObject $acl;
    private CryptInterface|MockObject $crypt;
    private UserToUserGroupRepository|MockObject $userToUserGroupRepository;
    private AccountWriter $writer;

    #[Test]
    public function testUpdateBulkReturnsEarlyWhenEmpty(): void
    {
        $this->accountRepository->expects(self::never())->method('isVisible');
        $this->accountRepository->expects(self::never())->method('transactionAware');

        $this->writer->updateBulk(new AccountUpdateBulkDto([], []));
    }

    #[Test]
    public function testUpdateBulkRejectsMissingForeignItems(): void
    {
        $this->accountRepository->method('isVisible')->with(10)->willReturn(true);

        $dtoClient = new AccountUpdateDto(clientId: 99);
        $this->clientRepository->expects(self::once())->method('getById')->with(99)->willReturn(new QueryResult([]));

        try {
            $this->writer->updateBulk(new AccountUpdateBulkDto([10], [$dtoClient]));
            self::fail('Expected ValidationException for missing client');
        } catch (ValidationException $e) {
            self::assertSame('A client is needed', $e->getMessage());
        }

        $dtoCategory = new AccountUpdateDto(categoryId: 88);
        $this->categoryRepository->expects(self::once())->method('getById')->with(88)->willReturn(new QueryResult([]));

        try {
            $this->writer->updateBulk(new AccountUpdateBulkDto([10], [$dtoCategory]));
            self::fail('Expected ValidationException for missing category');
        } catch (ValidationException $e) {
            self::assertSame('A category is needed', $e->getMessage());
        }

        $dtoUser = new AccountUpdateDto(userId: 77);
        $this->userRepository->expects(self::once())->method('getById')->with(77)->willReturn(new QueryResult([]));

        try {
            $this->writer->updateBulk(new AccountUpdateBulkDto([10], [$dtoUser]));
            self::fail('Expected ValidationException for missing user');
        } catch (ValidationException $e) {
            self::assertSame('An owner is needed', $e->getMessage());
        }

        $dtoGroup = new AccountUpdateDto(userGroupId: 66);
        $this->userGroupRepository->expects(self::once())->method('getById')->with(66)->willReturn(new QueryResult([]));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('A user group is needed');

        $this->writer->updateBulk(new AccountUpdateBulkDto([10], [$dtoGroup]));
    }

    #[Test]
    public function testUpdateBulkAppliesFixedPresetDeletesHistoryCopiesCustomFieldsAndRevokesLinks(): void
    {
        $this->setAdminUser(10, 20);

        $account10 = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 10,
            'clientId' => 1,
            'categoryId' => 2,
            'userId' => 10,
            'userGroupId' => 20,
            'passDateChange' => 0,
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
        ]);
        $account11 = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 11,
            'clientId' => 1,
            'categoryId' => 2,
            'userId' => 10,
            'userGroupId' => 20,
            'passDateChange' => 100,
            'isPrivate' => 1,
            'isPrivateGroup' => 1,
        ]);

        $preset = new ItemPreset([
            'fixed' => 1,
            'data' => serialize(new AccountPrivate(true, true)),
        ]);

        $this->accountRepository->method('isVisible')->willReturn(true);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountRepository->method('getById')->willReturnCallback(
            static fn(int $id): QueryResult => new QueryResult([$id === 10 ? $account10 : $account11])
        );
        $this->accountRepository->method('getChildIdsByParentId')->willReturnCallback(
            static fn(int $id): array => $id === 10 ? [30] : []
        );

        $this->accountHistoryService->expects(self::once())->method('deleteByAccountIdBatch')->with([10, 11]);
        $this->itemPresetService->expects(self::once())
            ->method('getForCurrentUser')
            ->with(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
            ->willReturn($preset);

        $this->configService->expects(self::once())->method('getByParam')->with('masterPwd')->willReturn('master');
        $this->accountHistoryService->expects(self::once())->method('create')->willReturn(500);
        $this->customFieldDataService->expects(self::once())
            ->method('copyByModuleAndItem')
            ->with(AclActionsInterface::ACCOUNT, 10, AclActionsInterface::ACCOUNT_HISTORY_VIEW, 500);

        $this->accountRepository->expects(self::once())->method('updateBulk');
        $this->accountItemsService->expects(self::once())->method('updateItems');
        $this->publicLinkRepository->expects(self::once())->method('deleteByItemIds')->with([10, 30]);

        $dto10 = new AccountUpdateDto(passDateChange: 500);
        // $account11 already matches preset privacy (private=true, privateGroup=true) and no other changes -> skipped!
        $dto11 = new AccountUpdateDto();

        $this->writer->updateBulk(new AccountUpdateBulkDto([10, 11], [$dto10, $dto11], true));
    }

    #[Test]
    public function testUpdateBulkDetectsChangesAcrossFieldsTagsAndGrantsAndSkipsUnchangedNonAdmin(): void
    {
        $this->setAdminUser(10, 20);
        $account = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 10,
            'clientId' => 1,
            'categoryId' => 2,
            'userId' => 10,
            'userGroupId' => 20,
            'passDateChange' => 100,
        ]);

        $this->accountRepository->method('isVisible')->willReturn(true);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountRepository->method('getById')->with(10)->willReturn(new QueryResult([$account]));
        $this->accountRepository->method('getChildIdsByParentId')->with(10)->willReturn([]);
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 9])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 99, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 88])]));
        $this->itemPresetService->method('getForCurrentUser')->willReturn(null);
        $this->configService->method('getByParam')->willReturn('master');
        $this->accountHistoryService->method('create')->willReturn(0);

        // 1) categoryId change
        $this->writer->updateBulk(new AccountUpdateBulkDto([10], [new AccountUpdateDto(categoryId: 9)]));
        // 2) userId change
        $this->writer->updateBulk(new AccountUpdateBulkDto([10], [new AccountUpdateDto(userId: 99)]));
        // 3) userGroupId change
        $this->writer->updateBulk(new AccountUpdateBulkDto([10], [new AccountUpdateDto(userGroupId: 88)]));
        // 4) tags change
        $this->accountToTagRepository->expects(self::once())
            ->method('getTagsByAccountId')
            ->with(10)
            ->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->writer->updateBulk(new AccountUpdateBulkDto([10], [new AccountUpdateDto(tags: [1, 2])]));
        // 5) grants change checked via hasAccountGrantsChanged
        $this->accountToUserRepository->expects(self::once())
            ->method('getUsersByAccountId')
            ->with(10)
            ->willReturn(new QueryResult([new AccountItem(['id' => 5, 'isEdit' => 0])]));
        $this->accountToUserGroupRepository->expects(self::once())
            ->method('getUserGroupsByAccountId')
            ->with(10)
            ->willReturn(new QueryResult([new AccountItem(['id' => 6, 'isEdit' => 1])]));
        $this->writer->updateBulk(
            new AccountUpdateBulkDto(
                [10],
                [new AccountUpdateDto(usersView: [5], userGroupsEdit: [6])]
            )
        );

        // 6) non-admin with no scalar/tag changes returns false from hasBulkChanges without touching grants
        $this->setRegularUser(10, 20, new ProfileData());
        $this->writer->updateBulk(new AccountUpdateBulkDto([10], [new AccountUpdateDto(usersView: [999])]));
        self::addToAssertionCount(1);
    }

    #[Test]
    public function testCreateWithLinkedParentAndCustomFields(): void
    {
        $this->setAdminUser(10, 20);

        $parent = new AccountModel(['id' => 5, 'parentId' => 0]);
        $owner = new UserModel(['id' => 10, 'userGroupId' => 20]);

        $this->clientRepository->method('getById')->with(1)->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->with(2)->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->with(10)->willReturn(new QueryResult([$owner]));
        $this->userGroupRepository->method('getById')->with(20)->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->acl->method('checkUserAccess')->with(AclActionsInterface::ACCOUNT_VIEW_PASS)->willReturn(true);
        $this->accountRepository->method('isVisible')->with(5)->willReturn(true);
        $this->accountRepository->method('getById')->with(5)->willReturn(new QueryResult([$parent]));
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountRepository->method('create')->willReturn(new QueryResult(null, 0, 77));
        $this->accountCryptService->expects(self::never())->method('getPasswordEncrypted');

        $this->customFieldDataService->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn(CustomFieldDataModel $cf): bool => $cf->getItemId() === 77
                && $cf->getDefinitionId() === 3
                && $cf->getData() === 'cf-val'));

        $dto = new AccountCreateDto(
            clientId: 1,
            categoryId: 2,
            userId: 10,
            userGroupId: 20,
            parentId: 5,
            name: '  Linked Account  ',
            pass: '',
            customFields: [3 => 'cf-val', 4 => '']
        );

        self::assertSame(77, $this->writer->create($dto));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidCreateFieldsProvider(): array
    {
        return [
            'long login' => [['login' => str_repeat('a', 51)], 'Invalid login'],
            'long url' => [['url' => str_repeat('u', 256)], 'Invalid URL'],
            'missing client id' => [['clientId' => 0], 'A client is needed'],
            'unknown client id' => [['clientId' => 999], 'A client is needed'],
            'missing category id' => [['categoryId' => 0], 'A category is needed'],
            'unknown category id' => [['categoryId' => 999], 'A category is needed'],
            'missing user id' => [['userId' => 0], 'An owner is needed'],
            'unknown user id' => [['userId' => 999], 'An owner is needed'],
            'missing group id' => [['userGroupId' => 0], 'A user group is needed'],
            'unknown group id' => [['userGroupId' => 999], 'A user group is needed'],
        ];
    }

    #[DataProvider('invalidCreateFieldsProvider')]
    #[Test]
    public function testCreateRejectsInvalidFields(array $overrides, string $expectedMessage): void
    {
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());

        $this->clientRepository->method('getById')->willReturnCallback(
            static fn(int $id): QueryResult => new QueryResult($id === 999 ? [] : [new Item(['id' => $id])])
        );
        $this->categoryRepository->method('getById')->willReturnCallback(
            static fn(int $id): QueryResult => new QueryResult($id === 999 ? [] : [new Item(['id' => $id])])
        );
        $this->userRepository->method('getById')->willReturnCallback(
            static fn(int $id): QueryResult => new QueryResult($id === 999 ? [] : [new UserModel(['id' => $id, 'userGroupId' => 20])])
        );
        $this->userGroupRepository->method('getById')->willReturnCallback(
            static fn(int $id): QueryResult => new QueryResult($id === 999 ? [] : [new Item(['id' => $id])])
        );

        $base = AccountDataGenerator::factory()->buildAccountCreateDto()->mutate(array_merge([
            'name' => 'Valid Account',
            'login' => 'user',
            'url' => 'https://example.test',
            'notes' => 'note',
            'clientId' => 1,
            'categoryId' => 2,
            'userId' => 10,
            'userGroupId' => 20,
            'parentId' => 0,
        ], $overrides));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->writer->create($base);
    }

    #[Test]
    public function testCreateEnforcesPrivacyAuthorityAndCompatibility(): void
    {
        $profile = new ProfileData();
        $profile->setAccPermission(true);
        $this->setRegularUser(10, 20, $profile);

        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 99])]));

        $dtoBase = new AccountCreateDto(
            clientId: 1,
            categoryId: 2,
            userId: 10,
            userGroupId: 20,
            parentId: 0,
            name: 'Valid Account',
            login: 'login',
            url: 'https://example.test',
            notes: ''
        );

        try {
            $this->writer->create($dtoBase->withPrivate(true));
            self::fail('Expected AccountPermissionException for private account without profile permission');
        } catch (AccountPermissionException) {
        }

        try {
            $this->writer->create($dtoBase->withPrivateGroup(true));
            self::fail('Expected AccountPermissionException for private group without profile permission');
        } catch (AccountPermissionException) {
        }

        // Admin setting both private & privateGroup with mismatched ownerGroupId vs userGroupId -> ValidationException
        $this->setAdminUser(10, 20);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid privacy configuration');

        $this->writer->create(
            $dtoBase->mutate([
                'userGroupId' => 99,
                'isPrivate' => true,
                'isPrivateGroup' => true,
            ])
        );
    }

    #[Test]
    public function testUpdateMergesDefaultsTrimsNameSkipsNoOpAndUpdatesChangedFields(): void
    {
        $this->setAdminUser(10, 20);

        $existing = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 50,
            'name' => 'Acct',
            'login' => 'login',
            'url' => 'https://example.test',
            'notes' => 'notes',
            'clientId' => 1,
            'categoryId' => 2,
            'userId' => 10,
            'userGroupId' => 20,
            'passDateChange' => 100,
            'parentId' => 0,
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
            'otherUserEdit' => 0,
            'otherUserGroupEdit' => 0,
        ]);

        $this->accountRepository->method('isVisible')->with(50)->willReturn(true);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountRepository->method('getById')->with(50)->willReturn(new QueryResult([$existing]));
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 88])]));

        // 1) All-null DTO matching existing -> early return (no update/history)
        $this->accountHistoryService->expects(self::once())->method('create')->willReturn(0);
        $this->accountRepository->expects(self::once())->method('update');

        $this->writer->update(50, new AccountUpdateDto());

        // 2) Update with whitespace name, unchanged encrypted custom field + cleared + updated custom field, tags & grants
        $this->customFieldDataService->expects(self::once())
            ->method('getBy')
            ->with(AclActionsInterface::ACCOUNT, 50)
            ->willReturn([
                ['definitionId' => 0, 'data' => 'skip', 'key' => ''],
                ['definitionId' => 1, 'data' => 'cipher', 'key' => 'k1'],
                ['definitionId' => 2, 'data' => 'old-plain', 'key' => ''],
            ]);
        $this->customFieldDataService->expects(self::once())
            ->method('decrypt')
            ->with('cipher', 'k1')
            ->willReturn('same');
        $this->customFieldDataService->expects(self::once())
            ->method('deleteByDefinition')
            ->with(50, AclActionsInterface::ACCOUNT, 1);
        $this->customFieldDataService->expects(self::once())
            ->method('updateOrCreate')
            ->with(self::callback(static fn(CustomFieldDataModel $m): bool => $m->getDefinitionId() === 2 && $m->getData() === 'new-plain'));

        $this->accountToTagRepository->expects(self::once())
            ->method('getTagsByAccountId')
            ->with(50)
            ->willReturn(new QueryResult([['id' => 7]]));
        $this->accountRepository->expects(self::once())->method('getChildIdsByParentId')->with(50)->willReturn([51]);
        $this->publicLinkRepository->expects(self::once())->method('deleteByItemIds')->with([50, 51]);

        $this->writer->update(
            50,
            new AccountUpdateDto(
                name: '  Acct New  ',
                tags: [7],
                customFields: [1 => '', 2 => 'new-plain']
            )
        );
    }

    #[Test]
    public function testUpdateEnforcesPrivacyAuthorityAndGroupChangeAndGrantsBranches(): void
    {
        $profile = new ProfileData();
        $profile->setAccPermission(true);
        $this->setRegularUser(10, 20, $profile);

        $existing = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 50,
            'name' => 'Acct',
            'login' => 'login',
            'url' => 'https://example.test',
            'notes' => '',
            'clientId' => 1,
            'categoryId' => 2,
            'userId' => 10,
            'userGroupId' => 20,
            'passDateChange' => 0,
            'parentId' => 0,
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
            'otherUserEdit' => 0,
            'otherUserGroupEdit' => 0,
        ]);

        $this->accountRepository->method('isVisible')->with(50)->willReturn(true);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountRepository->method('getById')->with(50)->willReturn(new QueryResult([$existing]));
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 25])]));

        // 1) Toggling isPrivate without permission
        try {
            $this->writer->update(50, new AccountUpdateDto(isPrivate: true));
            self::fail('Expected AccountPermissionException for private update');
        } catch (AccountPermissionException) {
        }

        // 2) Toggling isPrivateGroup without permission
        try {
            $this->writer->update(50, new AccountUpdateDto(isPrivateGroup: true));
            self::fail('Expected AccountPermissionException for private group update');
        } catch (AccountPermissionException) {
        }

        // 3) Group change + user/group grants unchanged path
        $this->accountToUserRepository->method('getUsersByAccountId')->with(50)->willReturn(
            new QueryResult([new AccountItem(['id' => 1, 'isEdit' => 0]), new AccountItem(['id' => 2, 'isEdit' => 1])])
        );
        $this->accountToUserGroupRepository->method('getUserGroupsByAccountId')->with(50)->willReturn(
            new QueryResult([new AccountItem(['id' => 3, 'isEdit' => 0]), new AccountItem(['id' => 4, 'isEdit' => 1])])
        );
        $this->accountRepository->expects(self::once())->method('update');

        $this->writer->update(
            50,
            new AccountUpdateDto(
                userGroupId: 25,
                usersView: [1],
                usersEdit: [2],
                userGroupsView: [3],
                userGroupsEdit: [4]
            )
        );
    }

    #[Test]
    public function testUpdateUnlinkFromParentChecksPermissionsDecryptFallbackAndNewPass(): void
    {
        $this->setAdminUser(10, 20);

        $existingLinked = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 60,
            'name' => 'Child',
            'login' => 'login',
            'url' => 'https://example.test',
            'notes' => '',
            'clientId' => 1,
            'categoryId' => 2,
            'userId' => 10,
            'userGroupId' => 20,
            'passDateChange' => 0,
            'parentId' => 5,
            'pass' => '',
            'key' => '',
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
            'otherUserEdit' => 0,
            'otherUserGroupEdit' => 0,
        ]);

        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));

        $this->accountRepository->method('getById')->willReturn(new QueryResult([$existingLinked]));

        // 1) Missing ACCOUNT_EDIT_PASS
        $this->accountRepository->method('isVisible')->willReturn(true);
        $this->acl->expects(self::once())
            ->method('checkUserAccess')
            ->with(AclActionsInterface::ACCOUNT_EDIT_PASS)
            ->willReturn(false);

        try {
            $this->writer->update(60, new AccountUpdateDto(parentId: 0));
            self::fail('Expected AccountPermissionException for missing EDIT_PASS');
        } catch (AccountPermissionException) {
        }

        // Rebuild writer with fresh ACL mock for remaining cases
        $this->resetMocksForWriter();
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->accountRepository->method('getById')->willReturn(new QueryResult([$existingLinked]));

        // 2) Missing ACCOUNT_VIEW_PASS
        $this->accountRepository->method('isVisible')->willReturn(true);
        $this->acl->method('checkUserAccess')->willReturnCallback(
            static fn(int $action): bool => $action !== AclActionsInterface::ACCOUNT_VIEW_PASS
        );

        try {
            $this->writer->update(60, new AccountUpdateDto(parentId: 0));
            self::fail('Expected AccountPermissionException for missing VIEW_PASS');
        } catch (AccountPermissionException) {
        }

        // 3) Old parent not visible
        $this->resetMocksForWriter();
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->accountRepository->method('getById')->willReturn(new QueryResult([$existingLinked]));
        $this->acl->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->method('isVisible')->willReturnCallback(static fn(int $id): bool => $id === 60);

        try {
            $this->writer->update(60, new AccountUpdateDto(parentId: 0));
            self::fail('Expected AccountPermissionException for invisible old parent');
        } catch (AccountPermissionException) {
        }

        // 4) Unlinking with empty pass & empty stored pass -> ValidationException
        $this->resetMocksForWriter();
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->accountRepository->method('getById')->willReturn(new QueryResult([$existingLinked]));
        $this->acl->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->method('isVisible')->willReturn(true);

        try {
            $this->writer->update(60, new AccountUpdateDto(parentId: 0, pass: ''));
            self::fail('Expected ValidationException when stored password empty');
        } catch (ValidationException $e) {
            self::assertSame('A key is needed', $e->getMessage());
        }

        // 5) Unlinking with existing stored pass that fails crypt::decrypt -> ValidationException
        $this->resetMocksForWriter();
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $existingWithBadCipher = $existingLinked->mutate(['pass' => 'bad-pass', 'key' => 'bad-key']);
        $this->accountRepository->method('getById')->willReturn(new QueryResult([$existingWithBadCipher]));
        $this->acl->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->method('isVisible')->willReturn(true);
        $this->context->setTrasientKey(\SP\Domain\Core\Context\Context::MASTER_PASSWORD_KEY, 'master');
        $this->crypt->expects(self::once())->method('decrypt')->willThrowException(new CryptException('bad'));

        try {
            $this->writer->update(60, new AccountUpdateDto(parentId: 0, pass: ''));
            self::fail('Expected ValidationException on decrypt failure');
        } catch (ValidationException $e) {
            self::assertSame('A key is needed', $e->getMessage());
        }

        // 6) Unlinking with new pass -> checks password preset & calls editPassword
        $this->resetMocksForWriter();
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->accountRepository->method('getById')->willReturn(new QueryResult([$existingLinked]));
        $this->acl->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->method('isVisible')->willReturn(true);

        $unlinkDto = new AccountUpdateDto(parentId: 0, pass: 'NewSecret123!');
        $this->accountPresetService->expects(self::once())
            ->method('checkPasswordPreset')
            ->willReturnArgument(0);
        $this->accountCryptService->expects(self::once())
            ->method('getPasswordEncrypted')
            ->with('NewSecret123!')
            ->willReturn(new EncryptedPassword('enc-pass', 'enc-key'));
        $this->accountRepository->expects(self::once())->method('editPassword');
        $this->accountRepository->expects(self::once())->method('update');

        $this->writer->update(60, $unlinkDto);
    }

    #[Test]
    public function testValidateParentLinkRejectionsOnUpdate(): void
    {
        $this->setAdminUser(10, 20);
        $acct = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 50,
            'name' => 'Acct',
            'login' => 'login',
            'url' => 'https://example.test',
            'notes' => '',
            'clientId' => 1,
            'categoryId' => 2,
            'userId' => 10,
            'userGroupId' => 20,
            'parentId' => 0,
        ]);

        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->accountRepository->method('isVisible')->willReturnCallback(static fn(int $id): bool => $id === 50);
        $this->accountRepository->method('getById')->willReturnCallback(
            static fn(int $id): QueryResult => new QueryResult($id === 50 ? [$acct] : [])
        );

        // 1) Self parent
        try {
            $this->writer->update(50, new AccountUpdateDto(parentId: 50));
            self::fail('Expected ValidationException for self-parent');
        } catch (ValidationException $e) {
            self::assertSame('Invalid parent account', $e->getMessage());
        }

        // 2) Account with existing children
        $this->accountRepository->method('getChildIdsByParentId')->with(50)->willReturn([70]);
        try {
            $this->writer->update(50, new AccountUpdateDto(parentId: 10));
            self::fail('Expected ValidationException when account has children');
        } catch (ValidationException $e) {
            self::assertSame('Invalid parent account', $e->getMessage());
        }

        // 3) Parent invisible
        $this->resetMocksForWriter();
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->accountRepository->method('getById')->willReturnCallback(
            static fn(int $id): QueryResult => new QueryResult($id === 50 ? [$acct] : [])
        );
        $this->accountRepository->method('getChildIdsByParentId')->willReturn([]);
        $this->acl->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->method('isVisible')->willReturnCallback(static fn(int $id): bool => $id === 50);

        try {
            $this->writer->update(50, new AccountUpdateDto(parentId: 10));
            self::fail('Expected AccountPermissionException for invisible parent');
        } catch (AccountPermissionException) {
        }

        // 4) Parent missing
        $this->resetMocksForWriter();
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->clientRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 1])]));
        $this->categoryRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 2])]));
        $this->userRepository->method('getById')->willReturn(new QueryResult([new UserModel(['id' => 10, 'userGroupId' => 20])]));
        $this->userGroupRepository->method('getById')->willReturn(new QueryResult([new Item(['id' => 20])]));
        $this->accountRepository->method('getById')->willReturnCallback(
            static fn(int $id): QueryResult => new QueryResult($id === 50 ? [$acct] : [])
        );
        $this->accountRepository->method('getChildIdsByParentId')->willReturn([]);
        $this->acl->method('checkUserAccess')->willReturn(true);
        $this->accountRepository->method('isVisible')->willReturn(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid parent account');

        $this->writer->update(50, new AccountUpdateDto(parentId: 10));
    }

    #[Test]
    public function testRestoreModifiedValidatesPreservesPasswordWithoutEditPassAndCopiesCustomFields(): void
    {
        $this->setAdminUser(10, 20);

        try {
            $this->writer->restoreModified(new AccountHistoryDto(id: 5, accountId: 0));
            self::fail('Expected AccountPermissionException for non-positive accountId');
        } catch (AccountPermissionException) {
        }

        $this->accountRepository->method('isVisible')->with(50)->willReturn(true);
        $this->clientRepository->method('getById')->willReturnOnConsecutiveCalls(
            new QueryResult([]),
            new QueryResult([new Item(['id' => 1])]),
            new QueryResult([new Item(['id' => 1])])
        );
        $this->categoryRepository->method('getById')->willReturnOnConsecutiveCalls(
            new QueryResult([]),
            new QueryResult([new Item(['id' => 2])])
        );

        try {
            $this->writer->restoreModified(new AccountHistoryDto(id: 5, accountId: 50, clientId: 1, categoryId: 2));
            self::fail('Expected ValidationException for missing client');
        } catch (ValidationException $e) {
            self::assertSame('A client is needed', $e->getMessage());
        }

        try {
            $this->writer->restoreModified(new AccountHistoryDto(id: 5, accountId: 50, clientId: 1, categoryId: 2));
            self::fail('Expected ValidationException for missing category');
        } catch (ValidationException $e) {
            self::assertSame('A category is needed', $e->getMessage());
        }

        $liveAccount = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 50,
            'pass' => 'live-pass',
            'key' => 'live-key',
            'parentId' => 9,
            'userId' => 10,
            'userGroupId' => 20,
        ]);

        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountRepository->method('getById')->with(50)->willReturn(new QueryResult([$liveAccount]));
        $this->acl->expects(self::once())->method('checkUserAccess')->with(AclActionsInterface::ACCOUNT_EDIT_PASS)->willReturn(false);
        $this->accountRepository->expects(self::once())
            ->method('restoreModified')
            ->with(50, self::callback(static fn(AccountModel $m): bool => $m->getPass() === 'live-pass'
                && $m->getKey() === 'live-key'
                && $m->getParentId() === 9))
            ->willReturn(new QueryResult(null, 1));
        $this->customFieldDataService->expects(self::once())->method('deleteByItem')->with(50, AclActionsInterface::ACCOUNT);
        $this->customFieldDataService->expects(self::once())
            ->method('copyByModuleAndItem')
            ->with(AclActionsInterface::ACCOUNT_HISTORY_VIEW, 5, AclActionsInterface::ACCOUNT, 50);
        $this->publicLinkRepository->expects(self::once())->method('deleteByItemIds')->with([50]);

        $this->writer->restoreModified(new AccountHistoryDto(
            id: 5,
            accountId: 50,
            clientId: 1,
            categoryId: 2,
            pass: 'old-pass',
            key: 'old-key',
            parentId: 0
        ));
    }

    #[Test]
    public function testRestoreRemovedEnforcesSnapshotValidationAuthorizationAndReassignsOccupiedId(): void
    {
        $this->setRegularUser(10, 20, new ProfileData());

        try {
            $this->writer->restoreRemoved(new AccountHistoryDto(isDeleted: false));
            self::fail('Expected ServiceException for non-deleted history dto');
        } catch (ServiceException) {
        }

        try {
            $this->writer->restoreRemoved(new AccountHistoryDto(id: 0, accountId: 50, isDeleted: true));
            self::fail('Expected AccountPermissionException for missing historyId');
        } catch (AccountPermissionException) {
        }

        $snapInvalid = new AccountHistoryModel(['id' => 5, 'accountId' => 50, 'isDeleted' => 1, 'pass' => '', 'key' => '', 'parentId' => 0]);
        $snapPrivate = new AccountHistoryModel(['id' => 5, 'accountId' => 50, 'isDeleted' => 1, 'pass' => 'p', 'key' => 'k', 'isPrivate' => 1, 'userId' => 99]);
        $snapPrivGroup = new AccountHistoryModel(['id' => 5, 'accountId' => 50, 'isDeleted' => 1, 'pass' => 'p', 'key' => 'k', 'isPrivateGroup' => 1, 'userGroupId' => 99]);
        $snapOtherGroup = new AccountHistoryModel(['id' => 5, 'accountId' => 50, 'isDeleted' => 1, 'pass' => 'p', 'key' => 'k', 'userId' => 99, 'userGroupId' => 30]);

        $this->accountHistoryService->method('getByAccountId')->with(50)->willReturnOnConsecutiveCalls(
            [],
            [$snapInvalid],
            [$snapPrivate],
            [$snapPrivGroup],
            [$snapOtherGroup],
            [$snapOtherGroup],
            [$snapOtherGroup],
            [$snapOtherGroup],
            [$snapOtherGroup],
            [$snapOtherGroup],
            [$snapOtherGroup]
        );

        foreach (range(1, 5) as $attempt) {
            if ($attempt === 5) {
                $this->userToUserGroupRepository->method('getGroupsForUser')->willReturn(new QueryResult([]));
            }
            try {
                $this->writer->restoreRemoved(new AccountHistoryDto(id: 5, accountId: 50, isDeleted: true));
                self::fail("Expected AccountPermissionException at attempt $attempt");
            } catch (AccountPermissionException) {
            }
        }

        // Now authorize via secondary group 30 and check validation & restoration branches
        $this->resetMocksForWriter();
        $this->setRegularUser(10, 20, new ProfileData());
        $this->accountHistoryService->method('getByAccountId')->with(50)->willReturn([$snapOtherGroup]);
        $this->userToUserGroupRepository->method('getGroupsForUser')
            ->with(10)
            ->willReturn(new QueryResult([new UserToUserGroup(['userId' => 10, 'userGroupId' => 30])]));

        $this->clientRepository->method('getById')->willReturnOnConsecutiveCalls(
            new QueryResult([]),
            new QueryResult([new Item(['id' => 1])]),
            new QueryResult([new Item(['id' => 1])]),
            new QueryResult([new Item(['id' => 1])]),
            new QueryResult([new Item(['id' => 1])]),
            new QueryResult([new Item(['id' => 1])]),
            new QueryResult([new Item(['id' => 1])])
        );
        $this->categoryRepository->method('getById')->willReturnOnConsecutiveCalls(
            new QueryResult([]),
            new QueryResult([new Item(['id' => 2])]),
            new QueryResult([new Item(['id' => 2])]),
            new QueryResult([new Item(['id' => 2])]),
            new QueryResult([new Item(['id' => 2])]),
            new QueryResult([new Item(['id' => 2])])
        );
        $this->userRepository->method('getById')->willReturnOnConsecutiveCalls(
            new QueryResult([]),
            new QueryResult([new UserModel(['id' => 99, 'userGroupId' => 30])]),
            new QueryResult([new UserModel(['id' => 99, 'userGroupId' => 30])]),
            new QueryResult([new UserModel(['id' => 99, 'userGroupId' => 30])]),
            new QueryResult([new UserModel(['id' => 99, 'userGroupId' => 30])])
        );
        $this->userGroupRepository->method('getById')->willReturnOnConsecutiveCalls(
            new QueryResult([]),
            new QueryResult([new Item(['id' => 30])]),
            new QueryResult([new Item(['id' => 30])]),
            new QueryResult([new Item(['id' => 30])])
        );

        $dto = new AccountHistoryDto(
            id: 5,
            clientId: 1,
            categoryId: 2,
            userId: 99,
            userGroupId: 30,
            name: 'Restored',
            accountId: 50,
            isDeleted: true,
            dateAdd: '2024-01-01 00:00:00'
        );

        foreach (['A client is needed', 'A category is needed', 'An owner is needed', 'A user group is needed'] as $msg) {
            try {
                $this->writer->restoreRemoved($dto);
                self::fail("Expected ValidationException: $msg");
            } catch (ValidationException $e) {
                self::assertSame($msg, $e->getMessage());
            }
        }

        // Test transaction branches: matching dateAdd -> ServiceException; zero affected -> ServiceException; success with reassignAccountId + custom fields
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountRepository->method('getById')->with(50)->willReturnOnConsecutiveCalls(
            new QueryResult([new AccountModel(['id' => 50, 'dateAdd' => '2024-01-01 00:00:00'])]),
            new QueryResult([new AccountModel(['id' => 50, 'dateAdd' => '2023-01-01 00:00:00'])]),
            new QueryResult([new AccountModel(['id' => 50, 'dateAdd' => '2023-01-01 00:00:00'])])
        );
        $this->accountRepository->method('createRemoved')->willReturnOnConsecutiveCalls(
            new QueryResult(null, 0, 0),
            new QueryResult(null, 1, 777)
        );
        $this->accountHistoryService->expects(self::once())->method('reassignAccountId')->with(50, 777, '2024-01-01 00:00:00');
        $this->customFieldDataService->expects(self::once())
            ->method('copyByModuleAndItem')
            ->with(AclActionsInterface::ACCOUNT_HISTORY_VIEW, 5, AclActionsInterface::ACCOUNT, 777);

        try {
            $this->writer->restoreRemoved($dto);
            self::fail('Expected ServiceException for matching dateAdd on occupied id');
        } catch (ServiceException $e) {
            self::assertSame('Error on restoring the account', $e->getMessage());
        }

        try {
            $this->writer->restoreRemoved($dto);
            self::fail('Expected ServiceException when createRemoved affects 0 rows');
        } catch (ServiceException $e) {
            self::assertSame('Error on restoring the account', $e->getMessage());
        }

        $this->writer->restoreRemoved($dto);
    }

    #[Test]
    public function testDeleteAndDeleteByIdBatchEdgeCases(): void
    {
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('isVisible')->willReturn(true);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());

        // 1) delete rejects account with children
        $this->accountRepository->expects(self::exactly(2))
            ->method('getChildIdsByParentId')
            ->willReturnOnConsecutiveCalls([99], [99], [20]);

        try {
            $this->writer->delete(10);
            self::fail('Expected ServiceException when deleting account with children');
        } catch (ServiceException $e) {
            self::assertSame('Error while deleting the account', $e->getMessage());
        }

        // 2) deleteByIdBatch rejects empty ids
        try {
            $this->writer->deleteByIdBatch([0, -1]);
            self::fail('Expected ServiceException for empty ids');
        } catch (ServiceException $e) {
            self::assertSame('Error while deleting the accounts', $e->getMessage());
        }

        // 3) deleteByIdBatch rejects parent whose child is not in batch
        try {
            $this->writer->deleteByIdBatch([10]);
            self::fail('Expected ServiceException when unincluded child exists');
        } catch (ServiceException $e) {
            self::assertSame('Error while deleting the account', $e->getMessage());
        }

        // 4) deleteByIdBatch with publicLinkRepository & customFieldDataService deletes history, custom fields, public links, and checks count
        $this->resetMocksForWriter();
        $this->setAdminUser(10, 20);
        $this->accountRepository->method('isVisible')->willReturn(true);
        $this->accountRepository->method('transactionAware')->willReturnCallback(static fn(callable $fn) => $fn());
        $this->accountRepository->method('getChildIdsByParentId')->willReturn([]);
        $this->accountRepository->method('getById')->willReturn(new QueryResult([AccountDataGenerator::factory()->buildAccount()]));
        $this->configService->method('getByParam')->willReturn('master');
        $this->accountHistoryService->method('create')->willReturn(0);
        $this->customFieldDataService->expects(self::once())->method('delete')->with([10, 11], AclActionsInterface::ACCOUNT);
        $this->publicLinkRepository->expects(self::once())->method('deleteByItemIds')->with([10, 11]);
        $this->accountRepository->expects(self::once())->method('deleteByIdBatch')->with([10, 11])->willReturn(new QueryResult(null, 2));

        $this->writer->deleteByIdBatch([10, 11]);
    }

    private function setAdminUser(int $id, int $groupId): void
    {
        $this->context->setUserData(
            UserDto::fromModel(
                UserDataGenerator::factory()->buildUserData()->mutate([
                    'id' => $id,
                    'userGroupId' => $groupId,
                    'isAdminApp' => true,
                    'isAdminAcc' => false,
                ])
            )
        );
        $this->context->setUserProfile(new ProfileData());
    }

    private function setRegularUser(int $id, int $groupId, ProfileData $profile): void
    {
        $this->context->setUserData(
            UserDto::fromModel(
                UserDataGenerator::factory()->buildUserData()->mutate([
                    'id' => $id,
                    'userGroupId' => $groupId,
                    'isAdminApp' => false,
                    'isAdminAcc' => false,
                ])
            )
        );
        $this->context->setUserProfile($profile);
    }

    private function resetMocksForWriter(): void
    {
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->itemPresetService = $this->createMock(ItemPresetService::class);
        $this->accountHistoryService = $this->createMock(AccountHistoryService::class);
        $this->accountItemsService = $this->createMock(AccountItemsService::class);
        $this->accountPresetService = $this->createMock(AccountPresetService::class);
        $this->configService = $this->createMock(ConfigService::class);
        $this->configService->method('getByParam')->willReturn('master');
        $this->accountCryptService = $this->createMock(AccountCryptService::class);
        $this->customFieldDataService = $this->createMock(CustomFieldDataService::class);
        $this->publicLinkRepository = $this->createMock(PublicLinkRepository::class);
        $this->clientRepository = $this->createMock(ClientRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userGroupRepository = $this->createMock(UserGroupRepository::class);
        $this->accountToTagRepository = $this->createMock(AccountToTagRepository::class);
        $this->accountToUserRepository = $this->createMock(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->createMock(AccountToUserGroupRepository::class);
        $this->acl = $this->createMock(AclInterface::class);
        $this->crypt = $this->createMock(CryptInterface::class);
        $this->userToUserGroupRepository = $this->createMock(UserToUserGroupRepository::class);

        $this->writer = new AccountWriter(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->accountRepository,
            $this->itemPresetService,
            $this->accountHistoryService,
            $this->accountItemsService,
            $this->accountPresetService,
            $this->configService,
            $this->accountCryptService,
            new AccountReader($this->accountRepository, null, null, null),
            $this->customFieldDataService,
            $this->publicLinkRepository,
            $this->clientRepository,
            $this->categoryRepository,
            $this->userRepository,
            $this->userGroupRepository,
            $this->accountToTagRepository,
            $this->accountToUserRepository,
            $this->accountToUserGroupRepository,
            $this->acl,
            $this->crypt,
            new StatelessMasterKeyStore($this->context),
            $this->userToUserGroupRepository
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetMocksForWriter();
    }
}
