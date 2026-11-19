<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Controllers\AccountHistoryManager;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Acl\Acl;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\UserDataGenerator;

use function DI\autowire;
use function DI\get;
use function SP\Tests\getDbHandler;

/**
 * DB-backed characterization tests for Web Account History & AccountManager
 * authorization, SQL lookup, and transaction boundaries against MariaDB.
 */
#[Group('integration')]
final class AccountHistoryDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const MASTER_PASS = '12345678900';

    #[Test]
    public function viewPassHistoryAndCopyPassHistoryLackHistoryAclAndFailWithMultipleHistoryRows(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccViewHistory(false);

        $user = $this->seedUser($pdo, 'hist_no_acl', 5, $profile);
        $singleAccountId = $this->seedAccount($pdo, 'Account With Single History', $user->id, 5);
        $multiAccountId = $this->seedAccount($pdo, 'Account With Multiple Histories', $user->id, 5);

        $hSingleId = $this->seedAccountHistory($pdo, 100, $singleAccountId, 'Hist Single', $user->id, 5, 'HistPassSingle!');
        $h1Id = $this->seedAccountHistory($pdo, 101, $multiAccountId, 'Hist One', $user->id, 5, 'HistPassOne!');
        $h2Id = $this->seedAccountHistory($pdo, 102, $multiAccountId, 'Hist Two', $user->id, 5, 'HistPassTwo!');

        // 1. account/viewHistory requires ACCOUNT_HISTORY_VIEW and is denied
        $viewHistoryRes = $this->dispatchAsUser('get', 'account/viewHistory/' . $hSingleId, $user, $profile);
        self::assertStringContainsString("You don't have permission to access this page", $viewHistoryRes['body']);

        // 2. When account has 1 history row, account/viewPassHistory and account/copyPassHistory
        //    only check ACCOUNT_VIEW_PASS (bypassing ACCOUNT_HISTORY_VIEW) and match AccountHistory.accountId
        $viewSingleRes = $this->dispatchAsUser('post', 'account/viewPassHistory/' . $singleAccountId, $user, $profile);
        $viewSingleJson = $this->assertJsonOk($viewSingleRes);
        self::assertStringContainsString('HistPassSingle!', (string)($viewSingleJson['data']['html'] ?? ''));

        $copySingleRes = $this->dispatchAsUser('post', 'account/copyPassHistory/' . $singleAccountId, $user, $profile);
        $copySingleJson = $this->assertJsonOk($copySingleRes, 'Password copied');
        self::assertSame('HistPassSingle!', $copySingleJson['data']['accpass'] ?? null);

        // 3. When account has 2+ history rows, WHERE AccountHistory.accountId = :accountId returns >1 row,
        //    causing QueryResult::getData() to return null and AccountReader::getPasswordHistoryForId() to throw TypeError ("Internal error")
        $viewMultiRes = $this->dispatchAsUser('post', 'account/viewPassHistory/' . $multiAccountId, $user, $profile);
        $viewMultiJson = $this->decodeJson($viewMultiRes['body']);
        self::assertSame('ERROR', $viewMultiJson['status'] ?? null);
        self::assertSame('Internal error', $viewMultiJson['description'] ?? null);

        $copyMultiRes = $this->dispatchAsUser('post', 'account/copyPassHistory/' . $multiAccountId, $user, $profile);
        $copyMultiJson = $this->decodeJson($copyMultiRes['body']);
        self::assertSame('ERROR', $copyMultiJson['status'] ?? null);
        self::assertSame('Internal error', $copyMultiJson['description'] ?? null);

        // 4. Passing actual history row id ($h2Id=102, or $h1Id=101, or $hSingleId=100) instead of accountId fails with "The account doesn't exist"
        $byHistoryIdRes = $this->dispatchAsUser('post', 'account/viewPassHistory/' . $h2Id, $user, $profile);
        $byHistoryIdJson = $this->decodeJson($byHistoryIdRes['body']);
        self::assertSame('ERROR', $byHistoryIdJson['status'] ?? null);
        self::assertSame("The account doesn't exist", $byHistoryIdJson['description'] ?? null);
    }

    #[Test]
    public function getHistoryForAccountMatchesHistoryIdInsteadOfAccountIdInViewDropdown(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewHistory(true);

        $user = $this->seedUser($pdo, 'hist_dropdown_user', 5, $profile);

        // Seed two accounts with known IDs >= 10
        $ownedAccountId = $this->seedAccount($pdo, 'Owned Account Ten', $user->id, 5, 10);
        $otherPrivateAccountId = $this->seedAccount($pdo, 'Admin Private Eleven', 1, 1, 11, 1, 0);
        $noCollisionAccountId = $this->seedAccount($pdo, 'Owned Account Twelve', $user->id, 5, 12);

        // History row id=20 belongs to accountId=10 (ownedAccountId), NOT id=10
        $this->seedAccountHistory(
            $pdo,
            20,
            $ownedAccountId,
            'Owned Snapshot',
            $user->id,
            5,
            'pass-20',
            1,
            0,
            '2026-02-03 04:05:06'
        );
        // History row id=10 belongs to otherPrivateAccountId=11, colliding with $ownedAccountId!
        $this->seedAccountHistory(
            $pdo,
            10,
            $otherPrivateAccountId,
            'Foreign Private Snapshot',
            1,
            1,
            'pass-10',
            1,
            0,
            '2026-09-09 09:09:09'
        );
        // History row id=21 belongs to noCollisionAccountId=12 (no history row has id=12)
        $this->seedAccountHistory(
            $pdo,
            21,
            $noCollisionAccountId,
            'No Collision Snapshot',
            $user->id,
            5,
            'pass-21',
            1,
            0,
            '2026-03-04 05:06:07'
        );

        // Viewing Account 10 shows history row id=10 (from Account 11 / admin) and omits row id=20
        $viewTen = $this->dispatchAsUser('get', 'account/view/' . $ownedAccountId, $user, $profile);
        $this->assertResponseWasDispatched($viewTen);
        self::assertStringContainsString('value="10"', $viewTen['body']);
        self::assertStringContainsString('2026-09-09 09:09:09 - admin', $viewTen['body']);
        self::assertStringNotContainsString('value="20"', $viewTen['body']);
        self::assertStringNotContainsString('2026-02-03 04:05:06', $viewTen['body']);

        // Viewing Account 12 renders no history select at all because no AccountHistory row has id=12
        $viewTwelve = $this->dispatchAsUser('get', 'account/view/' . $noCollisionAccountId, $user, $profile);
        $this->assertResponseWasDispatched($viewTwelve);
        self::assertStringNotContainsString('id="history_id"', $viewTwelve['body']);
        self::assertStringNotContainsString('2026-03-04 05:06:07', $viewTwelve['body']);
    }

    #[Test]
    public function saveEditRestoreAcceptsMismatchedHistoryAndAccountIdsAndFailsOnOmittedEncryptedColumns(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccEdit(true)
            ->setAccEditPass(true);

        $user = $this->seedUser($pdo, 'hist_restore_user', 5, $profile);
        $ownedAccountId = $this->seedAccount($pdo, 'User Owned Target', $user->id, 5);
        $roSharedAccountId = $this->seedAccount($pdo, 'Admin Shared RO Target', 1, 1);
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$roSharedAccountId, $user->id]);

        $privateAdminAccountId = $this->seedAccount($pdo, 'Admin Private Target', 1, 1, null, 1, 0);

        $roSharedHistId = $this->seedAccountHistory($pdo, null, $roSharedAccountId, 'RO Snapshot Name', 1, 1, 'hist-pass', 1, 0);
        $privHistId = $this->seedAccountHistory($pdo, null, $privateAdminAccountId, 'Private Snapshot Name', 1, 1, 'hist-pass', 1, 0);

        // 1. Mismatched route ($privHistId belongs to privateAdminAccountId, $id is $ownedAccountId):
        //    Controller checks isVisible($ownedAccountId), passes, then AccountWriter checks isVisible($privateAdminAccountId)
        $mismatchedPrivateRes = $this->dispatchAsUser(
            'post',
            'account/saveEditRestore/' . $privHistId . '/' . $ownedAccountId,
            $user,
            $profile
        );
        $this->assertJsonDenied($mismatchedPrivateRes, "You don't have permission to access this account");

        // 2. Mismatched route against visible RO account ($roSharedHistId belongs to $roSharedAccountId with isEdit=0):
        //    Both checks pass; fails only because AccountHistoryRepository::getById() omits pass/key (MariaDB NOT NULL)
        $mismatchedRoRes = $this->dispatchAsUser(
            'post',
            'account/saveEditRestore/' . $roSharedHistId . '/' . $ownedAccountId,
            $user,
            $profile
        );
        $mismatchedRoJson = $this->decodeJson($mismatchedRoRes['body']);
        self::assertSame('ERROR', $mismatchedRoJson['status'] ?? null);
        self::assertSame('Integrity constraint', $mismatchedRoJson['description'] ?? null);
        self::assertSame('Admin Shared RO Target', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $roSharedAccountId));
    }

    #[Test]
    public function accountHistoryManagerDeleteAndRestoreLackAclAndRestoreFailsNotNullInMariaDb(): void
    {
        $pdo = getDbHandler()->getConnection();

        // Completely unprivileged logged-in user (no mgmAccounts, no acc* flags, not admin)
        $emptyProfile = new ProfileData();
        $unprivilegedUser = $this->seedUser($pdo, 'unpriv_hist_mgr', 5, $emptyProfile);

        $privAdminAccountId = $this->seedAccount($pdo, 'Admin Priv Account', 1, 1, null, 1, 0);
        $visibleAccountId = $this->seedAccount($pdo, 'Group5 Visible Account', 1, 5, null, 0, 0);

        $adminHistDel1 = $this->seedAccountHistory($pdo, null, $privAdminAccountId, 'Admin Del Hist 1', 1, 1, 'p1', 0, 1);
        $adminHistDel2 = $this->seedAccountHistory($pdo, null, $privAdminAccountId, 'Admin Del Hist 2', 1, 1, 'p2', 0, 1);
        $adminHistDel3 = $this->seedAccountHistory($pdo, null, $privAdminAccountId, 'Admin Del Hist 3', 1, 1, 'p3', 0, 1);
        $visibleModHist = $this->seedAccountHistory($pdo, null, $visibleAccountId, 'Visible Mod Hist', 1, 5, 'p4', 1, 0);

        // 1. Unprivileged user deletes another user's AccountHistory row (single) + (batch)
        //    Also proves (string)$accountHistoryDto serializes via PrintableTrait::__toString() without \Error.
        $delSingle = $this->dispatchAsUser(
            'get',
            'accountHistoryManager/delete/' . $adminHistDel1,
            $unprivilegedUser,
            $emptyProfile
        );
        $this->assertJsonOk($delSingle, 'Account removed');
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $adminHistDel1));

        $delBatch = $this->dispatchAsUser(
            'post',
            'accountHistoryManager/delete',
            $unprivilegedUser,
            $emptyProfile,
            [],
            ['items' => [$adminHistDel2]]
        );
        $this->assertJsonOk($delBatch, 'Accounts removed');
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE id = ' . $adminHistDel2));

        // 2. Unprivileged user calls accountHistoryManager/restore on isModify=1 visible row:
        //    No ACL check; reaches restoreModified(), fails MariaDB NOT NULL on omitted pass/key, rolls back addHistory
        $histCountBefore = (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $visibleAccountId);
        $restoreMod = $this->dispatchAsUser(
            'get',
            'accountHistoryManager/restore/' . $visibleModHist,
            $unprivilegedUser,
            $emptyProfile
        );
        $restoreModJson = $this->decodeJson($restoreMod['body']);
        self::assertSame('ERROR', $restoreModJson['status'] ?? null);
        self::assertSame('Integrity constraint', $restoreModJson['description'] ?? null);
        self::assertSame(
            $histCountBefore,
            (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $visibleAccountId)
        );

        // 3. Unprivileged user calls accountHistoryManager/restore on isModify=0 private removed row:
        //    restoreRemoved() skips isVisible() completely and hits MariaDB NOT NULL on omitted pass/key
        $restoreRem = $this->dispatchAsUser(
            'get',
            'accountHistoryManager/restore/' . $adminHistDel3,
            $unprivilegedUser,
            $emptyProfile
        );
        $restoreRemJson = $this->decodeJson($restoreRem['body']);
        self::assertSame('ERROR', $restoreRemJson['status'] ?? null);
        self::assertSame('Integrity constraint', $restoreRemJson['description'] ?? null);
    }

    #[Test]
    public function accountHistoryManagerSearchExposesCrossUserAndPrivateHistoryMetadata(): void
    {
        $pdo = getDbHandler()->getConnection();

        $mgrProfile = (new ProfileData())->setMgmAccounts(true);
        $mgrUser = $this->seedUser($pdo, 'hist_mgr_search', 5, $mgrProfile);

        $privAccountId = $this->seedAccount($pdo, 'Secret Admin Priv', 1, 1, null, 1, 0);
        $this->seedAccountHistory($pdo, null, $privAccountId, 'Private History Metadata Leak', 1, 1, 'p', 1, 0);

        $res = $this->dispatchAsUser('get', 'accountHistoryManager/search', $mgrUser, $mgrProfile);
        $json = $this->assertJsonOk($res);
        self::assertStringContainsString('Private History Metadata Leak', (string)($json['data']['html'] ?? ''));
    }

    #[Test]
    public function bulkEditDeletesHistoryOutsideTransactionAndBatchDeleteSkipsDeletedSnapshot(): void
    {
        $pdo = getDbHandler()->getConnection();

        $adminProfile = $this->getUserProfile();
        $adminUser = $this->getUserDataDto();

        $a1 = $this->seedAccount($pdo, 'Bulk Target One', 1, 1);
        $a2 = $this->seedAccount($pdo, 'Bulk Target Two', 1, 1);
        $this->seedAccountHistory($pdo, null, $a1, 'A1 History Before Bulk', 1, 1, 'p1', 1, 0);
        $this->seedAccountHistory($pdo, null, $a2, 'A2 History Before Bulk', 1, 1, 'p2', 1, 0);

        // Force updateBulk() to fail FK constraint (client_id=999999) while delete_history=true
        $bulkRes = $this->dispatchAsUser(
            'post',
            'accountManager/saveBulkEdit',
            $adminUser,
            $adminProfile,
            [],
            [
                'itemsId' => $a1 . ',' . $a2,
                'client_id' => '999999',
                'delete_history' => 'true',
            ]
        );
        $bulkJson = $this->decodeJson($bulkRes['body']);
        self::assertSame('ERROR', $bulkJson['status'] ?? null);

        // Both accounts remain untouched, yet their AccountHistory rows were deleted before updateBulk()!
        self::assertSame('Bulk Target One', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $a1));
        self::assertSame('Bulk Target Two', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $a2));
        self::assertSame(
            0,
            (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId IN (' . $a1 . ',' . $a2 . ')')
        );

        // Single delete creates isDeleted=1 snapshot; batch delete does NOT
        $delSingle = $this->dispatchAsUser('get', 'accountManager/delete/' . $a1, $adminUser, $adminProfile);
        $this->assertJsonOk($delSingle, 'Account removed');
        self::assertSame(
            1,
            (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $a1 . ' AND isDeleted = 1')
        );

        $delBatch = $this->dispatchAsUser(
            'post',
            'accountManager/delete',
            $adminUser,
            $adminProfile,
            [],
            ['items' => [$a2]]
        );
        $this->assertJsonOk($delBatch, 'Accounts removed');
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $a2));
        self::assertSame(
            0,
            (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = ' . $a2 . ' AND isDeleted = 1')
        );
    }

    /**
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @return array{body: string, code: int}
     */
    private function dispatchAsUser(
        string      $method,
        string      $route,
        UserDto     $user,
        ProfileData $profile,
        array       $paramsGet = [],
        array       $paramsPost = []
    ): array {
        $context = $this->buildSessionContext($user, $profile);
        $configData = self::createConfiguredStub(ConfigDataInterface::class, $this->getConfigData());
        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        return $this->dispatch(
            $method,
            $route,
            $paramsGet,
            $paramsPost,
            [
                Context::class => $context,
                ConfigFileService::class => $configFileService,
                ConfigDataInterface::class => $configData,
                AclInterface::class => autowire(Acl::class)
                    ->constructorParameter('actions', get(ActionsInterface::class)),
                AccountAclService::class => autowire(AccountAcl::class)
                    ->constructorParameter('fileCache', null),
            ]
        );
    }

    private function buildSessionContext(UserDto $user, ProfileData $profile): SessionContext
    {
        $sidStartTime = time();
        $vault = self::createStub(\SP\Domain\Core\Crypt\VaultInterface::class);
        $vault->method('getData')->willReturn(self::MASTER_PASS);
        $vault->method('reKey')->willReturnSelf();
        $accountsCache = null;
        $transient = ['_masterpass' => self::MASTER_PASS];

        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(true);
        $context->method('getUserData')->willReturn($user);
        $context->method('getUserProfile')->willReturn($profile);
        $context->method('getCSRF')->willReturn(null);
        $context->method('getSearchFilters')->willReturn(null);
        $context->method('getSidStartTime')->willReturn($sidStartTime);
        $context->method('getVault')->willReturn($vault);
        $context->method('getAccountsCache')->willReturnCallback(static function () use (&$accountsCache) {
            return $accountsCache;
        });
        $context->method('setAccountsCache')->willReturnCallback(static function (array $cache) use (&$accountsCache): void {
            $accountsCache = $cache;
        });
        $context->method('getTrasientKey')->willReturnCallback(
            static fn(string $key, mixed $default = null) => $transient[$key] ?? $default
        );
        $context->method('setTrasientKey')->willReturnCallback(
            static function (string $key, mixed $value) use (&$transient) {
                $transient[$key] = $value;

                return $value;
            }
        );

        return $context;
    }

    private function seedUser(PDO $pdo, string $login, int $userGroupId, ProfileData $profile): UserDto
    {
        $profileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (?, ?)');
        $profileStmt->execute(['Profile ' . $login, Serde::serialize($profile)]);
        $profileId = (int)$pdo->lastInsertId();

        $userStmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate, lastUpdateMPass, preferences)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, ?, 0, 0, 0, 0, 1528236611, ?)'
        );
        $userStmt->execute([
            'Synthetic ' . $login,
            $userGroupId,
            $login,
            Hash::hashKey('synthetic-pass-' . $login),
            $login . '@example.test',
            $profileId,
            sha1($login),
            Serde::serialize(new UserPreferences(['lang' => 'en_US'])),
        ]);
        $userId = (int)$pdo->lastInsertId();

        return UserDto::fromModel(UserDataGenerator::factory()->buildUserData())->mutate([
            'id' => $userId,
            'userGroupId' => $userGroupId,
            'userProfileId' => $profileId,
            'login' => $login,
            'ssoLogin' => $login,
            'name' => 'Synthetic ' . $login,
            'email' => $login . '@example.test',
            'lastUpdateMPass' => 1528236611,
            'isAdminApp' => false,
            'isAdminAcc' => false,
            'isDisabled' => false,
            'isChangePass' => false,
            'isChangedPass' => false,
            'isLdap' => false,
            'isMigrate' => false,
            'preferences' => new UserPreferences(['lang' => 'en_US']),
        ]);
    }

    private function seedAccount(
        PDO    $pdo,
        string $name,
        int    $userId,
        int    $userGroupId,
        ?int   $explicitId = null,
        int    $isPrivate = 0,
        int    $isPrivateGroup = 0
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt('account-secret', $key, self::MASTER_PASS);

        if ($explicitId !== null) {
            $stmt = $pdo->prepare(
                'INSERT INTO Account (id, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
                 VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), ?, ?, UNIX_TIMESTAMP(), 0)'
            );
            $stmt->execute([
                $explicitId,
                $userGroupId,
                $userId,
                $userId,
                $name,
                'acc-login-' . $explicitId,
                'https://example.test',
                $encPass,
                $key,
                'notes',
                $isPrivate,
                $isPrivateGroup,
            ]);

            return $explicitId;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO Account (userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
             VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), ?, ?, UNIX_TIMESTAMP(), 0)'
        );
        $stmt->execute([
            $userGroupId,
            $userId,
            $userId,
            $name,
            'acc-login',
            'https://example.test',
            $encPass,
            $key,
            'notes',
            $isPrivate,
            $isPrivateGroup,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function seedAccountHistory(
        PDO    $pdo,
        ?int   $explicitId,
        int    $accountId,
        string $name,
        int    $userId,
        int    $userGroupId,
        string $plainPass,
        int    $isModify = 1,
        int    $isDeleted = 0,
        string $dateEdit = '2026-01-01 00:00:00'
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt($plainPass, $key, self::MASTER_PASS);

        if ($explicitId !== null) {
            $stmt = $pdo->prepare(
                'INSERT INTO AccountHistory (id, accountId, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, dateEdit, isModify, isDeleted, mPassHash, passDate)
                 VALUES (?, ?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())'
            );
            $stmt->execute([
                $explicitId,
                $accountId,
                $userGroupId,
                $userId,
                $userId,
                $name,
                'hist-login-' . $explicitId,
                'https://hist.example.test',
                $encPass,
                $key,
                'hist notes',
                '2026-01-01 00:00:00',
                $dateEdit,
                $isModify,
                $isDeleted,
                Hash::hashKey(self::MASTER_PASS),
            ]);

            return $explicitId;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO AccountHistory (accountId, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, dateEdit, isModify, isDeleted, mPassHash, passDate)
             VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())'
        );
        $stmt->execute([
            $accountId,
            $userGroupId,
            $userId,
            $userId,
            $name,
            'hist-login',
            'https://hist.example.test',
            $encPass,
            $key,
            'hist notes',
            '2026-01-01 00:00:00',
            $dateEdit,
            $isModify,
            $isDeleted,
            Hash::hashKey(self::MASTER_PASS),
        ]);

        return (int)$pdo->lastInsertId();
    }
}
